<?php

namespace App\Http\Controllers\Api\V1\User\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\V1\User\Auth\AuthController;
use App\Models\Post;
use App\Models\Friendship;
use App\Models\UserEducation;
use App\Models\UserJob;
use App\Models\UserLink;
use App\Models\Skill;
use Illuminate\Validation\ValidationException;
use Exception;

class ProfileController extends Controller
{
    //

    private function mapEducationDetails($educations)
    {
        return $educations->map(function ($education) {
            return [
                'id' => $education->id,
                'education_level' => $education->education_level,
                'education_level_display' => $education->education_level_display,
                'institution_name' => $education->institution_name,
                'field_of_study' => $education->field_of_study,
                'description' => $education->description,
                'status' => $education->status,
                'status_display' => $education->status_display,
                'start_year' => $education->start_year,
                'end_year' => $education->end_year,
                'duration' => $education->duration,
                'is_current' => $education->is_current,
                'sort_order' => $education->sort_order,
                'created_at' => $education->created_at,
                'updated_at' => $education->updated_at
            ];
        });
    }
    
    private function mapJobDetails($jobs)
    {
        return $jobs->map(function ($job) {
            return [
                'id' => $job->id,
                'job_title' => $job->job_title,
                'company_name' => $job->company_name,
                'location' => $job->location,
                'employment_type' => $job->employment_type,
                'employment_type_display' => $job->employment_type_display,
                'start_date' => $job->start_date,
                'end_date' => $job->end_date,
                'duration' => $job->duration,
                'duration_in_months' => $job->duration_in_months,
                'duration_in_years_months' => $job->duration_in_years_months,
                'industry' => $job->industry,
                'job_description' => $job->job_description,
                'responsibilities' => $job->responsibilities,
                'skills_used' => $job->skills_used,
                'is_current_job' => $job->is_current_job,
                'is_previous_job' => $job->is_previous_job,
                'is_currently_working' => $job->is_currently_working,
                'sort_order' => $job->sort_order,
                'created_at' => $job->created_at,
                'updated_at' => $job->updated_at
            ];
        });
    }

    private function mapLinkDetails($links)
    {
        return $links->map(function ($link) {
            return [
                'id' => $link->id,
                'link_type' => $link->link_type,
                'link_type_display' => $link->link_type_display,
                'link_url' => $link->link_url,
                'title' => $link->title,
                'description' => $link->description,
                'is_active' => $link->is_active,
                'sort_order' => $link->sort_order,
                'link_icon' => $link->link_icon,
                'domain' => $link->domain,
                'is_valid_url' => $link->is_valid_url,
                'created_at' => $link->created_at,
                'updated_at' => $link->updated_at
            ];
        });
    }

    private function mapSkillDetails($skills)
    {
        return $skills->map(function ($skill) {
            return [
                'id' => $skill->id,
                'skill_name' => $skill->name,
                'category' => $skill->category,
                'proficiency_level' => $skill->pivot->proficiency_level,
                'proficiency_display' => $this->getProficiencyDisplay($skill->pivot->proficiency_level),
                'description' => $skill->pivot->description,
                'is_public' => $skill->pivot->is_public,
                'created_at' => $skill->pivot->created_at,
                'updated_at' => $skill->pivot->updated_at
            ];
        });
    }

    private function getProficiencyDisplay($level)
    {
        $levels = [
            1 => 'Beginner',
            2 => 'Elementary',
            3 => 'Intermediate',
            4 => 'Advanced',
            5 => 'Expert'
        ];

        return $levels[$level] ?? 'Unknown';
    }

    public function getMyInfo(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $userDetails = app(AuthController::class)->mapUserDetails($user);

        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Profile fetched successfully',
            'data' => [
                'user' => $userDetails
            ]
        ], 200);
    }

    public function getTotalPosts(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $totalPosts = Post::where('user_id', $user->id)->count();

        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Total posts fetched successfully',
            'data' => [
                'total_posts' => $totalPosts
            ]
        ], 200);
    }

    public function getTotalFriends(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return response()->json([
                'status_code' => 404,
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Count accepted friendships (same logic as FriendController)
        $totalFriends = Friendship::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('friend_id', $user->id);
        })
        ->where('status', 'accepted')
        ->count();

        return response()->json([
            'status_code' => 200,
            'success' => true,
            'message' => 'Total friends fetched successfully',
            'data' => [
                'total_friends' => $totalFriends
            ]
        ], 200);
    }

    public function getMyEducations(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:education_level,start_year,end_year,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
                'status' => 'nullable|string|in:currently_studying,currently_enrolled,graduated,completed,did_not_graduate,dropped_out,all'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $sortBy = $validatedData['sort_by'] ?? 'created_at';
            $sortOrder = $validatedData['sort_order'] ?? 'desc';
            $statusFilter = $validatedData['status'] ?? 'all';

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Build query
            $query = UserEducation::where('user_id', $user->id);

            // Apply status filter
            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }

            // Apply sorting
            if ($sortBy === 'education_level') {
                $query->orderBy('education_level', $sortOrder);
            } elseif ($sortBy === 'start_year') {
                $query->orderBy('start_year', $sortOrder);
            } elseif ($sortBy === 'end_year') {
                $query->orderBy('end_year', $sortOrder);
            } else {
                $query->orderBy('created_at', $sortOrder);
            }

            // Get educations with pagination
            $educations = $query->paginate($perPage);

            if ($educations->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No educations found',
                    'data' => [
                        'educations' => [],
                        'total_educations' => 0,
                        'current_filter' => $statusFilter,
                        'sorting' => [
                            'sort_by' => $sortBy,
                            'sort_order' => $sortOrder
                        ],
                        'pagination' => [
                            'current_page' => $educations->currentPage(),
                            'last_page' => $educations->lastPage(),
                            'per_page' => $educations->perPage(),
                            'total' => $educations->total(),
                            'from' => $educations->firstItem(),
                            'to' => $educations->lastItem(),
                            'has_more_pages' => $educations->hasMorePages()
                        ]
                    ]
                ], 200);
            }

            // Map educations data with additional computed attributes
            $mappedEducations = $this->mapEducationDetails($educations->getCollection());

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'My educations fetched successfully',
                'data' => [
                    'educations' => $mappedEducations,
                    'total_educations' => $educations->total(),
                    'current_filter' => $statusFilter,
                    'sorting' => [
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder
                    ],
                    'pagination' => [
                        'current_page' => $educations->currentPage(),
                        'last_page' => $educations->lastPage(),
                        'per_page' => $educations->perPage(),
                        'total' => $educations->total(),
                        'from' => $educations->firstItem(),
                        'to' => $educations->lastItem(),
                        'has_more_pages' => $educations->hasMorePages()
                    ]
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to fetch educations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMyJobs(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:job_title,company_name,start_date,end_date,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
                'employment_type' => 'nullable|string|in:full_time,part_time,internship,freelance,contract,self_employed,all',
                'job_status' => 'nullable|string|in:current,previous,all'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $sortBy = $validatedData['sort_by'] ?? 'created_at';
            $sortOrder = $validatedData['sort_order'] ?? 'desc';
            $employmentTypeFilter = $validatedData['employment_type'] ?? 'all';
            $jobStatusFilter = $validatedData['job_status'] ?? 'all';

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Build query
            $query = UserJob::where('user_id', $user->id);

            // Apply employment type filter
            if ($employmentTypeFilter !== 'all') {
                $query->where('employment_type', $employmentTypeFilter);
            }

            // Apply job status filter
            if ($jobStatusFilter === 'current') {
                $query->where('is_current_job', true);
            } elseif ($jobStatusFilter === 'previous') {
                $query->where('is_previous_job', true);
            }

            // Apply sorting
            if ($sortBy === 'job_title') {
                $query->orderBy('job_title', $sortOrder);
            } elseif ($sortBy === 'company_name') {
                $query->orderBy('company_name', $sortOrder);
            } elseif ($sortBy === 'start_date') {
                $query->orderBy('start_date', $sortOrder);
            } elseif ($sortBy === 'end_date') {
                $query->orderBy('end_date', $sortOrder);
            } else {
                $query->orderBy('created_at', $sortOrder);
            }

            // Get jobs with pagination
            $jobs = $query->paginate($perPage);

            if ($jobs->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No jobs found',
                    'data' => [
                        'jobs' => [],
                        'total_jobs' => 0,
                        'current_filters' => [
                            'employment_type' => $employmentTypeFilter,
                            'job_status' => $jobStatusFilter
                        ],
                        'sorting' => [
                            'sort_by' => $sortBy,
                            'sort_order' => $sortOrder
                        ],
                        'pagination' => [
                            'current_page' => $jobs->currentPage(),
                            'last_page' => $jobs->lastPage(),
                            'per_page' => $jobs->perPage(),
                            'total' => $jobs->total(),
                            'from' => $jobs->firstItem(),
                            'to' => $jobs->lastItem(),
                            'has_more_pages' => $jobs->hasMorePages()
                        ]
                    ]
                ], 200);
            }

            // Map jobs data with additional computed attributes
            $mappedJobs = $this->mapJobDetails($jobs->getCollection());

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'My jobs fetched successfully',
                'data' => [
                    'jobs' => $mappedJobs,
                    'total_jobs' => $jobs->total(),
                    'current_filters' => [
                        'employment_type' => $employmentTypeFilter,
                        'job_status' => $jobStatusFilter
                    ],
                    'sorting' => [
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder
                    ],
                    'pagination' => [
                        'current_page' => $jobs->currentPage(),
                        'last_page' => $jobs->lastPage(),
                        'per_page' => $jobs->perPage(),
                        'total' => $jobs->total(),
                        'from' => $jobs->firstItem(),
                        'to' => $jobs->lastItem(),
                        'has_more_pages' => $jobs->hasMorePages()
                    ]
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to fetch jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMyLinks(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:link_type,title,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
                'link_type' => 'nullable|string|in:website,portfolio,blog,linkedin,twitter,facebook,instagram,youtube,tiktok,github,behance,dribbble,pinterest,snapchat,telegram,whatsapp,other,all',
                'status' => 'nullable|string|in:active,inactive,all'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $sortBy = $validatedData['sort_by'] ?? 'created_at';
            $sortOrder = $validatedData['sort_order'] ?? 'desc';
            $linkTypeFilter = $validatedData['link_type'] ?? 'all';
            $statusFilter = $validatedData['status'] ?? 'all';

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Build query
            $query = UserLink::where('user_id', $user->id);

            // Apply link type filter
            if ($linkTypeFilter !== 'all') {
                $query->where('link_type', $linkTypeFilter);
            }

            // Apply status filter
            if ($statusFilter === 'active') {
                $query->where('is_active', true);
            } elseif ($statusFilter === 'inactive') {
                $query->where('is_active', false);
            }

            // Apply sorting
            if ($sortBy === 'link_type') {
                $query->orderBy('link_type', $sortOrder);
            } elseif ($sortBy === 'title') {
                $query->orderBy('title', $sortOrder);
            } else {
                $query->orderBy('created_at', $sortOrder);
            }

            // Get links with pagination
            $links = $query->paginate($perPage);

            if ($links->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No links found',
                    'data' => [
                        'links' => [],
                        'total_links' => 0,
                        'current_filters' => [
                            'link_type' => $linkTypeFilter,
                            'status' => $statusFilter
                        ],
                        'sorting' => [
                            'sort_by' => $sortBy,
                            'sort_order' => $sortOrder
                        ],
                        'pagination' => [
                            'current_page' => $links->currentPage(),
                            'last_page' => $links->lastPage(),
                            'per_page' => $links->perPage(),
                            'total' => $links->total(),
                            'from' => $links->firstItem(),
                            'to' => $links->lastItem(),
                            'has_more_pages' => $links->hasMorePages()
                        ]
                    ]
                ], 200);
            }

            // Map links data with additional computed attributes
            $mappedLinks = $this->mapLinkDetails($links->getCollection());

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'My links fetched successfully',
                'data' => [
                    'links' => $mappedLinks,
                    'total_links' => $links->total(),
                    'current_filters' => [
                        'link_type' => $linkTypeFilter,
                        'status' => $statusFilter
                    ],
                    'sorting' => [
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder
                    ],
                    'pagination' => [
                        'current_page' => $links->currentPage(),
                        'last_page' => $links->lastPage(),
                        'per_page' => $links->perPage(),
                        'total' => $links->total(),
                        'from' => $links->firstItem(),
                        'to' => $links->lastItem(),
                        'has_more_pages' => $links->hasMorePages()
                    ]
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to fetch links',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMySkills(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:skill_name,category,proficiency_level,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
                'category' => 'nullable|string|in:Technology & Digital,Creative & Arts,Business & Finance,Lifestyle & Personal Growth,Science & Education,Social & Community,Gaming & Entertainment,all',
                'proficiency_level' => 'nullable|integer|in:1,2,3,4,5',
                'visibility' => 'nullable|string|in:public,private,all'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $sortBy = $validatedData['sort_by'] ?? 'created_at';
            $sortOrder = $validatedData['sort_order'] ?? 'desc';
            $categoryFilter = $validatedData['category'] ?? 'all';
            $proficiencyFilter = $validatedData['proficiency_level'] ?? null;
            $visibilityFilter = $validatedData['visibility'] ?? 'all';

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Build query using the skills relationship
            $query = $user->skills();

            // Apply category filter
            if ($categoryFilter !== 'all') {
                $query->where('skills.category', $categoryFilter);
            }

            // Apply proficiency level filter
            if ($proficiencyFilter) {
                $query->wherePivot('proficiency_level', $proficiencyFilter);
            }

            // Apply visibility filter
            if ($visibilityFilter === 'public') {
                $query->wherePivot('is_public', true);
            } elseif ($visibilityFilter === 'private') {
                $query->wherePivot('is_public', false);
            }

            // Apply sorting
            if ($sortBy === 'skill_name') {
                $query->orderBy('skills.name', $sortOrder);
            } elseif ($sortBy === 'category') {
                $query->orderBy('skills.category', $sortOrder);
            } elseif ($sortBy === 'proficiency_level') {
                $query->orderBy('user_skills.proficiency_level', $sortOrder);
            } else {
                $query->orderBy('user_skills.created_at', $sortOrder);
            }

            // Get skills with pagination
            $skills = $query->paginate($perPage);

            if ($skills->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No skills found',
                    'data' => [
                        'skills' => [],
                        'total_skills' => 0,
                        'current_filters' => [
                            'category' => $categoryFilter,
                            'proficiency_level' => $proficiencyFilter,
                            'visibility' => $visibilityFilter
                        ],
                        'sorting' => [
                            'sort_by' => $sortBy,
                            'sort_order' => $sortOrder
                        ],
                        'pagination' => [
                            'current_page' => $skills->currentPage(),
                            'last_page' => $skills->lastPage(),
                            'per_page' => $skills->perPage(),
                            'total' => $skills->total(),
                            'from' => $skills->firstItem(),
                            'to' => $skills->lastItem(),
                            'has_more_pages' => $skills->hasMorePages()
                        ]
                    ]
                ], 200);
            }

            // Map skills data with additional computed attributes
            $mappedSkills = $this->mapSkillDetails($skills->getCollection());

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'My skills fetched successfully',
                'data' => [
                    'skills' => $mappedSkills,
                    'total_skills' => $skills->total(),
                    'current_filters' => [
                        'category' => $categoryFilter,
                        'proficiency_level' => $proficiencyFilter,
                        'visibility' => $visibilityFilter
                    ],
                    'sorting' => [
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder
                    ],
                    'pagination' => [
                        'current_page' => $skills->currentPage(),
                        'last_page' => $skills->lastPage(),
                        'per_page' => $skills->perPage(),
                        'total' => $skills->total(),
                        'from' => $skills->firstItem(),
                        'to' => $skills->lastItem(),
                        'has_more_pages' => $skills->hasMorePages()
                    ]
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to fetch skills',
                'error' => $e->getMessage()
            ], 500);
        }
    }
   

}
