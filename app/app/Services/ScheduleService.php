<?php

namespace App\Services;

use App\Models\Schedule;

class ScheduleService
{
    public function upsert(int $userId, string $date, ?string $content): Schedule
    {
        return Schedule::updateOrCreate(
            ['user_id' => $userId, 'date' => $date],
            ['content' => $content]
        );
    }

    public function getByUserAndRange(int $userId, string $startDate, string $endDate): array
    {
        return Schedule::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /** 전체 팀 스케줄: [user_id => ['date' => content]] */
    public function getTeamSchedules(string $startDate, string $endDate): array
    {
        return Schedule::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('user_id')
            ->map(fn($rows) => $rows->mapWithKeys(fn($s) => [
                $s->date->format('Y-m-d') => $s->content ?? ''
            ])->toArray())
            ->toArray();
    }
}
