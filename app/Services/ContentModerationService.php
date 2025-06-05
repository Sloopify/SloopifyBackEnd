<?php

// Services/ContentModerationService.php
namespace App\Services;

use App\Models\Post;
use App\Models\ContentModerationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContentModerationService
{
    private $bannedWords = [
        'spam', 'scam', 'hate', 'violence', 'abuse', 'terrorist',
        'drug', 'illegal', 'fraud', 'racist', 'sexist'
        // Add more banned words as needed
    ];

    private $toxicityThreshold = 0.7;
    private $spamThreshold = 0.8;

    public function moderatePost(Post $post)
    {
        $issues = [];
        $toxicityScore = 0;
        $spamScore = 0;
        $flaggedWords = [];
        $actionTaken = 'approved';
        $reasoning = '';

        // Check text content
        if ($post->content) {
            $textAnalysis = $this->analyzeText($post->content);
            $issues = array_merge($issues, $textAnalysis['issues']);
            $toxicityScore = $textAnalysis['toxicity_score'];
            $spamScore = $textAnalysis['spam_score'];
            $flaggedWords = $textAnalysis['flagged_words'];
        }

        // Check media content (basic check)
        foreach ($post->media as $media) {
            $mediaAnalysis = $this->analyzeMedia($media);
            if (!empty($mediaAnalysis['issues'])) {
                $issues = array_merge($issues, $mediaAnalysis['issues']);
            }
        }

        // Determine action based on analysis
        if ($toxicityScore >= $this->toxicityThreshold || $spamScore >= $this->spamThreshold || !empty($flaggedWords)) {
            $actionTaken = 'rejected';
            $reasoning = $this->generateRejectionReason($toxicityScore, $spamScore, $flaggedWords, $issues);
            $post->update([
                'status' => 'rejected',
                'moderation_reason' => $reasoning
            ]);
        } elseif ($toxicityScore >= 0.5 || $spamScore >= 0.6) {
            $actionTaken = 'flagged_for_review';
            $reasoning = 'Flagged for manual review due to potential issues';
            $post->update([
                'status' => 'pending',
                'moderation_reason' => $reasoning
            ]);
        } else {
            $post->update(['status' => 'approved']);
        }

        // Log moderation results
        ContentModerationLog::create([
            'post_id' => $post->id,
            'detected_issues' => $issues,
            'toxicity_score' => $toxicityScore,
            'spam_score' => $spamScore,
            'flagged_words' => $flaggedWords,
            'action_taken' => $actionTaken,
            'ai_reasoning' => $reasoning
        ]);

        return [
            'action' => $actionTaken,
            'reasoning' => $reasoning,
            'scores' => [
                'toxicity' => $toxicityScore,
                'spam' => $spamScore
            ]
        ];
    }

    private function analyzeText($text)
    {
        $issues = [];
        $flaggedWords = [];
        $toxicityScore = 0;
        $spamScore = 0;

        // Basic banned words check
        $lowerText = strtolower($text);
        foreach ($this->bannedWords as $word) {
            if (strpos($lowerText, $word) !== false) {
                $flaggedWords[] = $word;
                $issues[] = "Contains banned word: {$word}";
            }
        }

        // Simulate AI-based toxicity analysis
        $toxicityScore = $this->calculateToxicityScore($text);
        $spamScore = $this->calculateSpamScore($text);

        // Check for excessive caps
        if ($this->hasExcessiveCaps($text)) {
            $issues[] = 'Excessive use of capital letters';
            $spamScore += 0.2;
        }

        // Check for excessive repetition
        if ($this->hasExcessiveRepetition($text)) {
            $issues[] = 'Excessive repetition of words or characters';
            $spamScore += 0.3;
        }

        // Check for URLs (potential spam)
        if ($this->containsUrls($text)) {
            $issues[] = 'Contains URLs';
            $spamScore += 0.1;
        }

        return [
            'issues' => $issues,
            'toxicity_score' => min($toxicityScore, 1.0),
            'spam_score' => min($spamScore, 1.0),
            'flagged_words' => $flaggedWords
        ];
    }

    private function analyzeMedia($media)
    {
        $issues = [];

        // Basic media validation
        if ($media->size > 50 * 1024 * 1024) { // 50MB limit
            $issues[] = 'Media file too large';
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/avi'];
        if (!in_array($media->mime_type, $allowedTypes)) {
            $issues[] = 'Unsupported file type';
        }

        // In production, you would integrate with image/video analysis APIs
        // like Google Cloud Vision API, Amazon Rekognition, etc.

        return ['issues' => $issues];
    }

    private function calculateToxicityScore($text)
    {
        // Simulate toxicity analysis
        // In production, integrate with services like:
        // - Google Perspective API
        // - Azure Content Moderator
        // - Custom ML models

        $score = 0;
        $negativeWords = ['hate', 'stupid', 'idiot', 'kill', 'die', 'terrible'];
        
        foreach ($negativeWords as $word) {
            if (stripos($text, $word) !== false) {
                $score += 0.3;
            }
        }

        return min($score, 1.0);
    }

    private function calculateSpamScore($text)
    {
        $score = 0;

        // Check for spam indicators
        if (preg_match_all('/\b(buy|sale|discount|offer|free|win|prize)\b/i', $text) > 3) {
            $score += 0.4;
        }

        if (preg_match_all('/[!]{2,}/', $text) > 0) {
            $score += 0.2;
        }

        if (strlen($text) > 1000 && str_word_count($text) < 50) {
            $score += 0.3; // Lots of characters but few words (possible spam)
        }

        return min($score, 1.0);
    }

    private function hasExcessiveCaps($text)
    {
        $capsCount = strlen(preg_replace('/[^A-Z]/', '', $text));
        $totalLetters = strlen(preg_replace('/[^A-Za-z]/', '', $text));
        
        return $totalLetters > 0 && ($capsCount / $totalLetters) > 0.7;
    }

    private function hasExcessiveRepetition($text)
    {
        // Check for repeated characters
        if (preg_match('/(.)\1{4,}/', $text)) {
            return true;
        }

        // Check for repeated words
        $words = str_word_count(strtolower($text), 1);
        $wordCounts = array_count_values($words);
        
        foreach ($wordCounts as $count) {
            if ($count > 5) {
                return true;
            }
        }

        return false;
    }

    private function containsUrls($text)
    {
        return preg_match('/https?:\/\/[^\s]+/', $text) > 0;
    }

    private function generateRejectionReason($toxicityScore, $spamScore, $flaggedWords, $issues)
    {
        $reasons = [];

        if ($toxicityScore >= $this->toxicityThreshold) {
            $reasons[] = "Content appears to be toxic or harmful (score: {$toxicityScore})";
        }

        if ($spamScore >= $this->spamThreshold) {
            $reasons[] = "Content appears to be spam (score: {$spamScore})";
        }

        if (!empty($flaggedWords)) {
            $reasons[] = "Contains inappropriate words: " . implode(', ', $flaggedWords);
        }

        if (!empty($issues)) {
            $reasons = array_merge($reasons, $issues);
        }

        return implode('. ', $reasons);
    }

    public function reprocessPost(Post $post)
    {
        return $this->moderatePost($post);
    }
}