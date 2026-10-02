<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Feedback;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{

    /**
     * Get total feedback count for dashboard card
     *
     * @return JsonResponse
     */
    public function getCount()
    {
        try {
            $count = Feedback::count();

            return response()->json([
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch feedback count',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get feedback data for chart visualization
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Get all feedback records with pagination and search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search');

            // Limit max per page to 50
            if ($perPage > 50) {
                $perPage = 50;
            }

            $query = Feedback::with('requisitionForm')
                ->orderBy('created_at', 'desc');

            // Apply search filter if provided.
            // Rating columns are now numeric; match against a score when the search
            // term looks like a label (e.g. "satisfactory" → 3) OR a number.
            if ($search) {
                $labelScore = Feedback::labelToScore($search);
                $query->where(function ($q) use ($search, $labelScore) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('additional_feedback', 'like', "%{$search}%");
                    if ($labelScore !== null) {
                        $q->orWhere('system_performance', $labelScore)
                            ->orWhere('booking_experience', $labelScore)
                            ->orWhere('ease_of_use', $labelScore)
                            ->orWhere('useability', $labelScore);
                    }
                });
            }

            $feedback = $query->paginate($perPage);

            return response()->json($feedback);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch feedback',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validate the incoming request.
            // Front-end submits string labels; we accept any label defined in Feedback::RATING_LABELS
            // plus the legacy richer label set below, then normalize to tinyint 1–5.
            $validator = Validator::make($request->all(), [
                'email' => 'nullable|email|max:255',
                'request_id' => 'nullable|exists:requisition_forms,request_id',
                'system_performance' => 'required|string|max:32',
                'booking_experience' => 'required|string|max:32',
                'ease_of_use' => 'required|string|max:32',
                'useability' => 'required|string|max:32',
                'additional_feedback' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Legacy label normalizer — maps the older richer label sets to 1–5 scores.
            $legacyMap = [
                // system_performance / booking_experience variants
                'poor' => 1, 'fair' => 2, 'satisfactory' => 3, 'good' => 4, 'very good' => 5, 'excellent' => 5, 'outstanding' => 5,
                // ease_of_use
                'very difficult' => 1, 'difficult' => 2, 'neutral' => 3, 'easy' => 4, 'very easy' => 5,
                // useability
                'very unlikely' => 1, 'unlikely' => 2, 'likely' => 4, 'very likely' => 5,
            ];

            $normalize = function (?string $label) use ($legacyMap): ?int {
                if (!$label) return null;
                $direct = Feedback::labelToScore($label);
                if ($direct !== null) return $direct;
                return $legacyMap[strtolower(trim($label))] ?? null;
            };

            $validated['system_performance'] = $normalize($validated['system_performance']);
            $validated['booking_experience'] = $normalize($validated['booking_experience']);
            $validated['ease_of_use'] = $normalize($validated['ease_of_use']);
            $validated['useability'] = $normalize($validated['useability']);

            // Create the feedback
            $feedback = Feedback::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Feedback submitted successfully!',
                'data' => $feedback
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFeedbackData(Request $request)
    {
        try {
            $period = $request->query('period', 'all');

            $query = Feedback::query();
            if ($period !== 'all') {
                $days = (int) $period;
                $query->where('created_at', '>=', now()->subDays($days));
            }

            $feedbackData = $query->get();

            // Rating distribution per category, keyed by score 1–5.
            $emptyDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            $categories = ['system_performance', 'booking_experience', 'ease_of_use', 'useability'];
            $distribution = [];
            foreach ($categories as $cat) {
                $distribution[$cat] = $emptyDistribution;
            }

            $ratingSum = 0;
            $ratingCount = 0;

            foreach ($feedbackData as $fb) {
                foreach ($categories as $cat) {
                    $score = $fb->{$cat};
                    if ($score !== null && isset($distribution[$cat][$score])) {
                        $distribution[$cat][$score]++;
                    }
                }
                if ($fb->system_performance !== null) {
                    $ratingSum += $fb->system_performance;
                    $ratingCount++;
                }
            }

            $averageRating = $ratingCount > 0 ? $ratingSum / $ratingCount : 0;

            return response()->json([
                'system_performance' => $distribution['system_performance'],
                'booking_experience' => $distribution['booking_experience'],
                'ease_of_use' => $distribution['ease_of_use'],
                'useability' => $distribution['useability'],
                'total_feedback' => $feedbackData->count(),
                'average_rating' => round($averageRating, 2),
                'rating_labels' => Feedback::RATING_LABELS,
                'category_distribution' => [
                    'System Performance' => array_sum($distribution['system_performance']),
                    'Booking Experience' => array_sum($distribution['booking_experience']),
                    'Ease of Use' => array_sum($distribution['ease_of_use']),
                    'Likely to Recommend' => array_sum($distribution['useability']),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch feedback data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get feedback statistics for dashboard cards
     *
     * @return JsonResponse
     */
    public function getFeedbackStats()
    {
        try {
            $stats = [
                'total_feedback' => Feedback::count(),
                'average_rating' => round((float) Feedback::whereNotNull('system_performance')->avg('system_performance'), 2),
                // "Positive" = 4 or 5 on either system_performance or booking_experience.
                'positive_feedback' => Feedback::where(function ($q) {
                    $q->where('system_performance', '>=', 4)
                      ->orWhere('booking_experience', '>=', 4);
                })->count(),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch feedback statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}