    private function processCalendarEvents(array &$events, array $calendars, array $source, string $start_date, string $end_date): void
    {
    if (!class_exists($source['model'])) {
    return;
    }

    $model_ids = [];
    if ($source['type'] == 'Follow Up') {
    if (in_array($calendars, 'Leads Followup')) {
    $model_ids = array_merge($model_ids, $source['model']::whereHas('lead', fn($qu) => applyFilteringUser_new($qu, ['created_by', 'assigned_user']))->pluck('id')->toArray());
    }
    if (in_array($calendars, 'Client Followup')) {
    $model_ids = array_merge($model_ids, $source['model']::whereHas('client', fn($qu) => applyFilteringUser_new($qu, ['created_by', 'assigned_user']))->pluck('id')->toArray());
    }
    } else if ($source['type'] == 'Site Visit') {
    if (in_array($calendars, 'Leads Site Visit')) {
    $model_ids = array_merge($model_ids, $source['model']::whereHas('lead', fn($qu) => applyFilteringUser_new($qu, ['created_by', 'assigned_user']))->pluck('id')->toArray());
    }
    if (in_array($calendars, 'Client Site Visit')) {
    $model_ids = array_merge($model_ids, $source['model']::whereHas('client', fn($qu) => applyFilteringUser_new($qu, ['created_by', 'assigned_user']))->pluck('id')->toArray());
    }

    $model_ids = array_merge($model_ids, $source['model']::where(fn($qu) => applyFilteringUser_new($qu, ['visit_assignee']))->pluck('id')->toArray());
    }

    $query = $source['model']::query()->whereIn('id', $model_ids);

    // "Leads Followup","Leads Site Visit","Client Followup","Client Site Visit"

    // Apply filtering based on relationships and user permissions
    $query->where(function ($q) use ($source, $calendars) {
    for ($i = 1; $i <= $source['type']=='Site Visit' ? 3 : 2; $i++) {
        if ($i==1 && ($source['type']=='Follow Up' && in_array($calendars, 'Leads Followup' )) || ($source['type']=='Site Visit' && in_array($calendars, 'Leads Site Visit' ) ) ) {
        $q->where(function ($subQ) {
        $subQ->whereHas('lead', fn($qu) => applyFilteringUser_new($qu, ['created_by', 'assigned_user']));
        });
        } else if ($i == 2 && ($source['type'] == 'Follow Up' && in_array($calendars, 'Client Followup')) || ($source['type'] == 'Site Visit' && in_array($calendars, 'Client Site Visit') ) {
        $q->where(function ($subQ) {
        $subQ->whereHas('client', fn($qu) => applyFilteringUser_new($qu, ['created_by', 'assigned_user']));
        });
        } else if ($i == 3 && $source['type'] == 'Site Visit') {
        $q->where(function ($subQ) {
        $subQ->where(fn($qu) => applyFilteringUser_new($qu, ['visit_assignee']));
        });
        }
        }
        });

        // $query->where(function ($q) use ($source) {
        // if ($source['type'] === 'Site Visit') {
        // $q->where(function ($subQ) {
        // $subQ->whereHas('lead', fn($q) => applyFilteringUser_new($q, ['created_by', 'assigned_user']))
        // ->orWhereHas('client', fn($q) => applyFilteringUser_new($q, ['created_by', 'assigned_user']))
        // ->orWhere(fn($q) => applyFilteringUser_new($q, ['visit_assignee']));
        // });
        // } else {
        // $q->where(function ($subQ) {
        // $subQ->whereHas('lead', fn($q) => applyFilteringUser_new($q, ['created_by', 'assigned_user']))
        // ->orWhereHas('client', fn($q) => applyFilteringUser_new($q, ['created_by', 'assigned_user']));
        // });
        // }
        // });

        $query->whereBetween($source['dateField'], [$start_date, $end_date]);

        foreach ($query->get() as $item) {
        foreach ($source['types'] as $calendar => $info) {
        $field = $info['field'];
        if ($item->$field && $this->shouldIncludeEvent($calendars, $calendar)) {
        $events[] = $this->makeCalendarEvent(
        $info['prefix'],
        $item,
        $item->id,
        $item->title ?? ucfirst($info['prefix']),
        $item->{$source['dateField']},
        $calendar,
        url($info['url'] . $item->$field),
        [$field => $item->$field]
        );
        }
        }
        }
        }
