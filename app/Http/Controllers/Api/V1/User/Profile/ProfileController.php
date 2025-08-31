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
use App\Models\PostReaction;
use App\Models\Reaction;
use App\Models\User;
use App\Models\UserPlace;
use App\Models\Interest;
use App\Http\Controllers\Api\V1\User\Home\HomeController;
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

    private function mapInterests($interests)
    {
        // Convert array to collection if needed
        if (is_array($interests)) {
            $interests = collect($interests);
        }
        
        $groupedInterests = $interests->groupBy('category');
        
        return $groupedInterests->map(function ($categoryInterests, $categoryName) { 
            return [
                'category' => $categoryName,
                'interests' => $categoryInterests->map(function ($interest) {
                    return [
                        'id' => $interest->id,
                        'name' => $interest->name,
                        'web_icon' => $interest->web_icon ? config('app.url') . asset('storage/' . $interest->web_icon) : null,
                        'mobile_icon' => $interest->mobile_icon ? config('app.url') . asset('storage/' . $interest->mobile_icon) : null,
                        'status' => $interest->status,
                        'created_at' => $interest->created_at,
                        'updated_at' => $interest->updated_at,
                    ];
                })->values()
            ];
        })->values();
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

    public function getMyPosts(Request $request)
    {
        try {
            $validated = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50'
            ]);

            $user = Auth::guard('user')->user();
            $page = $validated['page'] ?? 1;
            $perPage = $validated['per_page'] ?? 20;

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Gather friend ids
            $friendships = Friendship::where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('friend_id', $user->id);
                })
                ->where('status', 'accepted')
                ->get();

            $friendIds = $friendships->map(function($f) use ($user) {
                return $f->user_id == $user->id ? $f->friend_id : $f->user_id;
            })->unique()->values();

            // Base query for posts visible to current user
            $visiblePostsQuery = Post::with(['user', 'media', 'poll', 'personalOccasion'])
                ->approved()
                ->notExpired()
                ->visibleTo($user->id);

            // 1) Direct friends' posts
            $friendsPosts = (clone $visiblePostsQuery)
                ->whereIn('user_id', $friendIds)
                ->inRandomOrder()
                ->take($perPage * 2)
                ->get();

            // 2) Posts friends interacted with (commented on or interest feedback)
            $friendInteractedPostIds = collect();
            if ($friendIds->isNotEmpty()) {
                $commented = \DB::table('post_comments')
                    ->whereIn('user_id', $friendIds)
                    ->pluck('post_id');
                $interested = \DB::table('post_interest_feedback')
                    ->whereIn('user_id', $friendIds)
                    ->pluck('post_id');
                $friendInteractedPostIds = $commented->merge($interested)->unique()->values();
            }

            $friendInteractedPosts = $friendInteractedPostIds->isEmpty()
                ? collect()
                : (clone $visiblePostsQuery)
                    ->whereIn('id', $friendInteractedPostIds)
                    ->inRandomOrder()
                    ->take($perPage)
                    ->get();

            // 3) Personalized suggested posts (interests + demographics)
            $userInterests = $user->userInterests()->pluck('interests.id');
            $gender = $user->gender;
            $age = $user->age;

            $suggestedQuery = (clone $visiblePostsQuery)
                ->whereNotIn('user_id', $friendIds->push($user->id))
                ->where('privacy', 'public');

            // Boost posts by users with overlapping interests
            if ($userInterests->isNotEmpty()) {
                $suggestedUserIdsByInterests = \DB::table('user_interests')
                    ->whereIn('interest_id', $userInterests)
                    ->where('user_id', '!=', $user->id)
                    ->pluck('user_id');
                $suggestedQuery->whereIn('user_id', $suggestedUserIdsByInterests);
            }

            // Light demographic alignment (optional filters if present)
            $suggestedQuery->when(!empty($gender), function($q) use ($gender) {
                $q->whereHas('user', function($uq) use ($gender) {
                    $uq->where('gender', $gender);
                });
            });
            $suggestedQuery->when(!empty($age), function($q) use ($age) {
                $q->whereHas('user', function($uq) use ($age) {
                    $uq->whereBetween('age', [max(13, $age - 5), $age + 5]);
                });
            });

            $suggestedPosts = $suggestedQuery
                ->inRandomOrder()
                ->take($perPage)
                ->get();

            // 4) Posts similar to ones user marked interested/not interested (use positive ones)
            $likedPostOwnerIds = \DB::table('post_interest_feedback')
                ->where('user_id', $user->id)
                ->where('interest_type', 'interested')
                ->pluck('post_owner_id');

            $similarPosts = $likedPostOwnerIds->isEmpty() ? collect() : (clone $visiblePostsQuery)
                ->whereIn('user_id', $likedPostOwnerIds)
                ->inRandomOrder()
                ->take($perPage)
                ->get();

            // Merge pools and de-duplicate by id
            $pool = $friendsPosts
                ->merge($friendInteractedPosts)
                ->merge($suggestedPosts)
                ->merge($similarPosts)
                ->unique('id')
                ->values();

            // Shuffle to keep it unordered and random per request
            $pool = $pool->shuffle();

            // Paginate manually from the shuffled pool
            $total = $pool->count();
            $offset = ($page - 1) * $perPage;
            $slice = $pool->slice($offset, $perPage)->values();

            // Enrich posts: comment_count, reaction_count, is_saved
            $postIds = $slice->pluck('id');
            $commentCounts = \DB::table('post_comments')
                ->whereIn('post_id', $postIds)
                ->whereNull('parent_comment_id')
                ->where('is_deleted', false)
                ->select('post_id', \DB::raw('count(*) as cnt'))
                ->groupBy('post_id')
                ->pluck('cnt', 'post_id');

            // Use interest feedback on posts as proxy for reactions
            $reactionCountsFriend = \DB::table('post_interest_feedback')
                ->whereIn('post_id', $postIds)
                ->select('post_id', \DB::raw('count(*) as cnt'))
                ->groupBy('post_id')
                ->pluck('cnt', 'post_id');
            $reactionCountsSuggested = \DB::table('suggested_post_interest_feedback')
                ->whereIn('post_id', $postIds)
                ->select('post_id', \DB::raw('count(*) as cnt'))
                ->groupBy('post_id')
                ->pluck('cnt', 'post_id');

            $savedPostIds = \DB::table('saved_posts')
                ->where('user_id', $user->id)
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->flip();

            // Get post reactions data with user details
            $postReactions = PostReaction::whereIn('post_id', $postIds)
                ->with(['reaction', 'user'])
                ->get()
                ->groupBy('post_id');

            // Get user's reactions for these posts
            $userReactions = PostReaction::where('user_id', $user->id)
                ->whereIn('post_id', $postIds)
                ->with('reaction')
                ->get()
                ->keyBy('post_id');

            $mapped = $slice->map(function($post) use ($commentCounts, $reactionCountsFriend, $reactionCountsSuggested, $savedPostIds, $friendIds, $postReactions, $userReactions) {
                $data = $post->toArray();
                $data['comments_count'] = (int) ($commentCounts[$post->id] ?? 0);
                
                // Get post reactions data for this post
                $postReactionData = $postReactions->get($post->id, collect());
                $totalPostReactions = $postReactionData->count();
                
                // Use new post reactions count instead of old interest feedback
                $data['reactions_count'] = $totalPostReactions;
                
                $data['is_saved'] = $savedPostIds->has($post->id);
                $data['is_user_friend'] = $friendIds->contains($post->user_id);
                
                // Map user data using mapUsersDetails function
                $data['user'] = $this->mapUsersDetails(collect([$post->user]))->first();
                
                // Map mentions friends to full user data
                if (isset($data['mentions']['friends']) && !empty($data['mentions']['friends'])) {
                    $mentionedUserIds = $data['mentions']['friends'];
                    $mentionedUsers = User::whereIn('id', $mentionedUserIds)->get();
                    $data['mentions']['friends'] = $this->mapUsersDetails($mentionedUsers);
                }
                
                // Map mentions place to full place data
                if (isset($data['mentions']['place']) && !empty($data['mentions']['place'])) {
                    $userPlace = UserPlace::find($data['mentions']['place']);
                    if ($userPlace) {
                        $data['mentions']['place'] = $this->mapUserPlaces($userPlace);
                    }
                }
                
                // Check if mentions object is empty and set to null
                if (isset($data['mentions']) && empty(array_filter($data['mentions']))) {
                    $data['mentions'] = null;
                }

                // Add post reactions data
                $userReactionData = $userReactions->get($post->id);

                // Group reactions by type and count them
                $reactionCounts = $postReactionData->groupBy('reaction_id')
                    ->map(function ($reactions) {
                        return [
                            'reaction' => $reactions->first()->reaction,
                            'count' => $reactions->count(),
                            'users' => $reactions->pluck('user')
                        ];
                    });

                $data['post_reactions'] = [
                    'user_reaction' => $userReactionData ? [
                        'id' => $userReactionData->reaction->id,
                        'name' => $userReactionData->reaction->name,
                        'content' => $userReactionData->reaction->content,
                        'image' => $this->formatReactionUrl($userReactionData->reaction->image_url),
                        'video' => $this->formatReactionUrl($userReactionData->reaction->video_url)
                    ] : null,
                    'reactions' => $reactionCounts->map(function ($item) {
                        return [
                            'id' => $item['reaction']->id,
                            'name' => $item['reaction']->name,
                            'content' => $item['reaction']->content,
                            'image' => $this->formatReactionUrl($item['reaction']->image_url),
                            'video' => $this->formatReactionUrl($item['reaction']->video_url),
                            'count' => $item['count'],
                            'users' => $this->mapUsersDetails($item['users'])
                        ];
                    })->values(),
                    'total_reactions' => $totalPostReactions
                ];
                
                // Date fields already present: created_at, updated_at
                return $data;
            });

            // Get reactions data from HomeController
            $homeController = app(HomeController::class);
            $reactionsRequest = new Request();
            $reactionsResponse = $homeController->getReactions($reactionsRequest);
            $reactionsData = json_decode($reactionsResponse->getContent(), true);

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'My feed retrieved successfully',
                'data' => [
                    'posts' => $mapped,
                    'reactions' => $reactionsData['success'] ? $reactionsData['data'] : [],
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => $total > 0 ? (int)ceil($total / $perPage) : 1,
                        'from' => $total ? $offset + 1 : null,
                        'to' => $total ? min($offset + $perPage, $total) : null,
                        'has_more_pages' => $offset + $perPage < $total
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
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'success' => false,
                'message' => 'Failed to retrieve feed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function mapUsersDetails($users)
    {
        return $users->map(function ($user) {
            return app(\App\Http\Controllers\Api\V1\User\Auth\AuthController::class)->mapUserDetails($user);
        });
    }
    
    public function mapUserPlaces($userPlace)
    {
        return app(\App\Http\Controllers\Api\V1\User\Post\PostController::class)->mapUserPlaces($userPlace);
    }

    private function formatReactionUrl($url)
    {
        return app(\App\Http\Controllers\Api\V1\User\Post\PostController::class)->formatReactionUrl($url);
    }

    public function getMyImagesFromPosts(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get posts that have images (type = image or have media with type = image)
            $postsWithImages = Post::with(['media'])
                ->where('user_id', $user->id)
                ->where(function($query) {
                    $query->where('type', 'image')
                          ->orWhereHas('media', function($mediaQuery) {
                              $mediaQuery->where('type', 'image');
                          });
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            if ($postsWithImages->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No images found in your posts',
                    'data' => [
                        'images' => [],
                        'total_images' => 0,
                        'pagination' => [
                            'current_page' => $postsWithImages->currentPage(),
                            'per_page' => $postsWithImages->perPage(),
                            'total' => $postsWithImages->total(),
                            'last_page' => $postsWithImages->lastPage(),
                            'from' => $postsWithImages->firstItem(),
                            'to' => $postsWithImages->lastItem(),
                            'has_more_pages' => $postsWithImages->hasMorePages()
                        ]
                    ]
                ], 200);
            }

            // Map posts to extract only image data
            $mappedImages = $postsWithImages->getCollection()->map(function ($post) {
                // Filter only image media
                $imageMedia = $post->media->filter(function ($media) {
                    return $media->type === 'image';
                })->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'type' => $media->type,
                        'url' => $media->url,
                        'thumbnail_url' => $media->thumbnail_url,
                        'file_size' => $media->file_size,
                        'width' => $media->width,
                        'height' => $media->height,
                        'metadata' => $media->metadata
                    ];
                })->values();

                return [
                    'post_id' => $post->id,
                    'post_content' => $post->content,
                    'post_type' => $post->type,
                    'images' => $imageMedia,
                    'created_at' => $post->created_at
                ];
            })->filter(function ($post) {
                // Only include posts that actually have images
                return $post['images']->isNotEmpty();
            })->values();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Images from my posts fetched successfully',
                'data' => [
                    'images' => $mappedImages,
                    'total_images' => $postsWithImages->total(),
                    'pagination' => [
                        'current_page' => $postsWithImages->currentPage(),
                        'per_page' => $postsWithImages->perPage(),
                        'total' => $postsWithImages->total(),
                        'last_page' => $postsWithImages->lastPage(),
                        'from' => $postsWithImages->firstItem(),
                        'to' => $postsWithImages->lastItem(),
                        'has_more_pages' => $postsWithImages->hasMorePages()
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
                'message' => 'Failed to fetch images from posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMyVideosFromPosts(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get posts that have videos (type = video or have media with type = video)
            $postsWithVideos = Post::with(['media'])
                ->where('user_id', $user->id)
                ->where(function($query) {
                    $query->where('type', 'video')
                          ->orWhereHas('media', function($mediaQuery) {
                              $mediaQuery->where('type', 'video');
                          });
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            if ($postsWithVideos->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No videos found in your posts',
                    'data' => [
                        'videos' => [],
                        'total_videos' => 0,
                        'pagination' => [
                            'current_page' => $postsWithVideos->currentPage(),
                            'per_page' => $postsWithVideos->perPage(),
                            'total' => $postsWithVideos->total(),
                            'last_page' => $postsWithVideos->lastPage(),
                            'from' => $postsWithVideos->firstItem(),
                            'to' => $postsWithVideos->lastItem(),
                            'has_more_pages' => $postsWithVideos->hasMorePages()
                        ]
                    ]
                ], 200);
            }

            // Map posts to extract only video data
            $mappedVideos = $postsWithVideos->getCollection()->map(function ($post) {
                // Filter only video media
                $videoMedia = $post->media->filter(function ($media) {
                    return $media->type === 'video';
                })->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'type' => $media->type,
                        'url' => $media->url,
                        'thumbnail_url' => $media->thumbnail_url,
                        'file_size' => $media->file_size,
                        'width' => $media->width,
                        'height' => $media->height,
                        'duration' => $media->duration,
                        'metadata' => $media->metadata
                    ];
                })->values();

                return [
                    'post_id' => $post->id,
                    'post_content' => $post->content,
                    'post_type' => $post->type,
                    'videos' => $videoMedia,
                    'created_at' => $post->created_at
                ];
            })->filter(function ($post) {
                // Only include posts that actually have videos
                return $post['videos']->isNotEmpty();
            })->values();

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'Videos from my posts fetched successfully',
                'data' => [
                    'videos' => $mappedVideos,
                    'total_videos' => $postsWithVideos->total(),
                    'pagination' => [
                        'current_page' => $postsWithVideos->currentPage(),
                        'per_page' => $postsWithVideos->perPage(),
                        'total' => $postsWithVideos->total(),
                        'last_page' => $postsWithVideos->lastPage(),
                        'from' => $postsWithVideos->firstItem(),
                        'to' => $postsWithVideos->lastItem(),
                        'has_more_pages' => $postsWithVideos->hasMorePages()
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
                'message' => 'Failed to fetch videos from posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMyInterests(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'category' => 'nullable|string|in:all',
                'status' => 'nullable|string|in:active,inactive,all'
            ]);

            $user = Auth::guard('user')->user();
            $perPage = $validatedData['per_page'] ?? 20;
            $categoryFilter = $validatedData['category'] ?? 'all';
            $statusFilter = $validatedData['status'] ?? 'all';

            if (!$user) {
                return response()->json([
                    'status_code' => 404,
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Build query using the interests relationship
            $query = $user->userInterests();

            // Apply category filter
            if ($categoryFilter && $categoryFilter !== 'all') {
                $query->where('interests.category', $categoryFilter);
            }

            // Apply status filter
            if ($statusFilter !== 'all') {
                $query->where('interests.status', $statusFilter);
            }

            // Get interests with pagination
            $interests = $query->paginate($perPage);

            if ($interests->isEmpty()) {
                return response()->json([
                    'status_code' => 200,
                    'success' => true,
                    'message' => 'No interests found',
                    'data' => [
                        'interests' => [],
                        'total_interests' => 0,
                        'current_filters' => [
                            'category' => $categoryFilter,
                            'status' => $statusFilter
                        ],
                        'pagination' => [
                            'current_page' => $interests->currentPage(),
                            'per_page' => $interests->perPage(),
                            'total' => $interests->total(),
                            'last_page' => $interests->lastPage(),
                            'from' => $interests->firstItem(),
                            'to' => $interests->lastItem(),
                            'has_more_pages' => $interests->hasMorePages()
                        ]
                    ]
                ], 200);
            }

            // Map interests data with grouping by category
            $mappedInterests = $this->mapInterests($interests->getCollection());

            return response()->json([
                'status_code' => 200,
                'success' => true,
                'message' => 'My interests fetched successfully',
                'data' => [
                    'interests' => $mappedInterests,
                    'total_interests' => $interests->total(),
                    'current_filters' => [
                        'category' => $categoryFilter,
                        'status' => $statusFilter
                    ],
                    'pagination' => [
                        'current_page' => $interests->currentPage(),
                        'per_page' => $interests->perPage(),
                        'total' => $interests->total(),
                        'last_page' => $interests->lastPage(),
                        'from' => $interests->firstItem(),
                        'to' => $interests->lastItem(),
                        'has_more_pages' => $interests->hasMorePages()
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
                'message' => 'Failed to fetch interests',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
