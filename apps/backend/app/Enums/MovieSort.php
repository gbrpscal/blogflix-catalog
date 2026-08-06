<?php

namespace App\Enums;

enum MovieSort: string
{
    case Releases = 'releases';
    case Highlights = 'highlights';
    case TitleAsc = 'title_asc';
    case TitleDesc = 'title_desc';

    public function tmdbValue(): string
    {
        return match ($this) {
            self::Releases => 'primary_release_date.desc',
            self::Highlights => 'vote_average.desc',
            self::TitleAsc => 'original_title.asc',
            self::TitleDesc => 'original_title.desc',
        };
    }

    public function minimumVoteCount(): ?int
    {
        return $this === self::Highlights ? 200 : null;
    }
}
