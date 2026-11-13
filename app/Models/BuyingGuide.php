<?php

namespace App\Models;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BuyingGuide extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'related_product_ids' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'reading_time_minutes' => 'integer',
        'views_count' => 'integer',
    ];

    /**
     * Relationship: Author
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Published only
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope: Filter by topic
     */
    public function scopeTopic($query, ?string $topic)
    {
        if (empty($topic)) {
            return $query;
        }
        return $query->where('topic', $topic);
    }

    /**
     * Accessor: Featured image full URL
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (empty($this->featured_image)) {
            return asset('images/guide-placeholder.jpg');
        }

        if (Str::startsWith($this->featured_image, ['http://', 'https://'])) {
            return $this->featured_image;
        }

        $trimmed = ltrim($this->featured_image, '/');
        if (Str::startsWith($trimmed, 'storage/')) {
            return url($trimmed);
        }

        return url(Storage::url($this->featured_image));
    }

    /**
     * Accessor: Related products Eloquent Collection
     */
    public function getRelatedProductsAttribute()
    {
        $ids = $this->related_product_ids ?? [];
        if (empty($ids) || !is_array($ids)) {
            return collect();
        }

        return Product::whereIn('id', $ids)
            ->where('is_active', true)
            ->with(['variants'])
            ->get();
    }

    /**
     * Parse Table of Contents from <h1>, <h2>, and <h3> tags in the HTML content
     * Returns an array of: [['id' => '...', 'title' => '...', 'level' => 1|2|3]]
     */
    public function getTableOfContentsAttribute(): array
    {
        $toc = [];
        if (empty($this->content)) {
            return $toc;
        }

        // Match h1, h2 and h3 elements
        $pattern = '/<(h[123])(?:\s+[^>]*)?>(.*?)<\/\1>/si';
        if (preg_match_all($pattern, $this->content, $matches, PREG_SET_ORDER)) {
            $usedIds = [];

            foreach ($matches as $match) {
                $level = (int) substr($match[1], 1); // 2 or 3
                $rawTitle = strip_tags($match[2]);
                $cleanTitle = html_entity_decode(trim($rawTitle));

                if (empty($cleanTitle)) {
                    continue;
                }

                // Generate slug id
                $slug = Str::slug($cleanTitle);
                if (empty($slug)) {
                    $slug = 'section-' . (count($toc) + 1);
                }

                // Ensure unique id per article
                $originalSlug = $slug;
                $counter = 1;
                while (in_array($slug, $usedIds)) {
                    $slug = "{$originalSlug}-{$counter}";
                    $counter++;
                }
                $usedIds[] = $slug;

                $toc[] = [
                    'id' => $slug,
                    'title' => $cleanTitle,
                    'level' => $level,
                ];
            }
        }

        return $toc;
    }

    /**
     * Processed Content: Injects matching anchor IDs and scroll-offset class into <h2> and <h3> tags
     */
    public function getProcessedContentAttribute(): string
    {
        if (empty($this->content)) {
            return '';
        }

        $usedIds = [];
        $pattern = '/<(h[123])(\s+[^>]*)?>(.*?)<\/\1>/si';

        return preg_replace_callback($pattern, function ($matches) use (&$usedIds) {
            $tag = $matches[1];
            $attributes = $matches[2] ?? '';
            $inner = $matches[3];

            $rawTitle = strip_tags($inner);
            $cleanTitle = html_entity_decode(trim($rawTitle));

            // Check if existing id attribute is present
            if (preg_match('/\bid=["\']([^"\']+)["\']/i', $attributes, $idMatch)) {
                $slug = $idMatch[1];
            } else {
                $slug = Str::slug($cleanTitle);
                if (empty($slug)) {
                    $slug = 'section-' . (count($usedIds) + 1);
                }
                $originalSlug = $slug;
                $counter = 1;
                while (in_array($slug, $usedIds)) {
                    $slug = "{$originalSlug}-{$counter}";
                    $counter++;
                }
            }
            $usedIds[] = $slug;

            // Strip existing id if any to prevent duplicate id attr
            $cleanAttrs = preg_replace('/\bid=["\'][^"\']*["\']/i', '', $attributes);
            // Append smooth scroll margin class
            if (preg_match('/class=["\']([^"\']*)["\']/i', $cleanAttrs, $classMatch)) {
                $newClasses = trim($classMatch[1] . ' scroll-mt-24');
                $cleanAttrs = preg_replace('/class=["\'][^"\']*["\']/i', 'class="' . $newClasses . '"', $cleanAttrs);
            } else {
                $cleanAttrs .= ' class="scroll-mt-24"';
            }

            return "<{$tag} id=\"{$slug}\" {$cleanAttrs}>{$inner}</{$tag}>";
        }, $this->content);
    }

    /**
     * Helper to compute estimated reading time in minutes
     */
    public static function estimateReadingTime(?string $content): int
    {
        if (empty($content)) {
            return 1;
        }

        $words = str_word_count(strip_tags($content));
        return max(1, (int) ceil($words / 200));
    }
}
