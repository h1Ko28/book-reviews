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

    public function scopePopular(Builder $query, $startDate = null, $endDate = null): Builder {
        return $query->withCount([
            "reviews" => fn (Builder $q) =>
                            $this->dateRangeFilter($q, $startDate, $endDate)])
            ->orderBy("reviews_count","desc");
    }

    public function scopeMinReviews(Builder $query, int $minReviews): Builder {
        return $query->having("reviews_count",">=", $minReviews);
    }

    public function scopeHighestRated(Builder $query, $startDate = null, $endDate = null): Builder {
        return $query->withAvg([
            "reviews" => fn (Builder $q) =>
                $this->dateRangeFilter($q, $startDate, $endDate)],"rating")
            ->orderBy("reviews_avg_rating","desc");
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
}
