<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Book extends Model
{
    use HasFactory;

    public function reviews() {
        return $this->hasMany(Review::class);
    }

    public function scopeTitle(Builder $query, string $title): Builder {
        return $query->where("title", "like", "%" . $title . "%");
    }


    public function scopeWithReviewsCount(Builder $query, $startDate = null, $endDate = null): Builder {
        return $query->withCount(["reviews" => fn (Builder $q) =>
            $this->dateRangeFilter($q, $startDate, $endDate)]);
    }

    public function scopeWithAverageRating(Builder $query, $startDate = null, $endDate = null): Builder {
        return $query->withAvg(["reviews" => fn (Builder $q) =>
            $this->dateRangeFilter($q, $startDate, $endDate)],"rating");
    }


    public function scopePopular(Builder $query, $startDate = null, $endDate = null): Builder {
        return $query->withReviewsCount()->orderBy("reviews_count","desc");
    }

    public function scopeMinReviews(Builder $query, int $minReviews): Builder {
        return $query->having("reviews_count",">=", $minReviews);
    }

    public function scopeHighestRated(Builder $query, $startDate = null, $endDate = null): Builder {
        return $query->withAverageRating()->orderBy("reviews_avg_rating","desc");
    }

    private function dateRangeFilter(Builder $query, $startDate, $endDate) {
        if ($startDate && !$endDate) {
            $query->where("created_at", '>=', '$startDate');
        } elseif (!$startDate && $endDate) {
            $query->where("created_at", '<=', '$endDate');
        } elseif ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
    }

    public function scopePopularLastMonth(Builder $query): Builder
    {
        return $query->popular(now()->subMonth(), now())
            ->highestRated(now()->subMonth(), now())
            ->minReviews(2);
    }

    public function scopePopularLast6Months(Builder $query): Builder
    {
        return $query->popular(now()->subMonths(6), now())
            ->highestRated(now()->subMonths(6), now())
            ->minReviews(5);
    }

    public function scopeHighestRatedLastMonth(Builder $query): Builder
    {
        return $query->highestRated(now()->subMonth(), now())
            ->popular(now()->subMonth(), now())
            ->minReviews(2);
    }

    public function scopeHighestRatedLast6Months(Builder $query): Builder
    {
        return $query->highestRated(now()->subMonths(6), now())
            ->popular(now()->subMonths(6), now())
            ->minReviews(5);
    }

    protected static function booted(): void {
        static::updated(fn(Book $book) => cache()->forget("book:" . $book->id));
        static::deleted(fn(Book $book) => cache()->forget("book:" . $book->id));
    }
}
