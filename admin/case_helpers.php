<?php
declare(strict_types=1);

function admin_case_date_label(?string $date): string
{
    $date = trim((string) $date);

    if ($date === '') {
        return 'Not set';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('M d, Y', $timestamp);
}

function admin_case_datetime_label(?string $date): string
{
    $date = trim((string) $date);

    if ($date === '') {
        return 'Not recorded';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('M d, Y h:i A', $timestamp);
}

function admin_case_status_label(string $status): string
{
    $normalized = strtolower(trim($status));

    if ($normalized === 'cfa' || $normalized === 'cfa (call for action)') {
        return 'CFA';
    }

    if ($normalized === 'm' || $normalized === 'mediation') {
        return 'M';
    }

    if ($normalized === 'c' || $normalized === 'conciliation' || $normalized === 'for conciliation stage') {
        return 'C';
    }

    return strtoupper($status);
}

function admin_case_badge_class(string $status): string
{
    $normalized = strtolower(trim($status));

    if ($normalized === 'cfa' || $normalized === 'cfa (call for action)') {
        return 'badge-cfa';
    }

    if ($normalized === 'm' || $normalized === 'mediation') {
        return 'badge-m';
    }

    if ($normalized === 'settled') {
        return 'badge-settled';
    }

    if ($normalized === 'dismissed') {
        return 'badge-dismissed';
    }

    if ($normalized === 'endorsed') {
        return 'badge-endorsed';
    }

    return 'badge-c';
}

function admin_case_page_url(int $page, string $search, string $status, string $dateFilter = '', string $dateValue = ''): string
{
    $query = ['page' => max(1, $page)];

    if ($search !== '') {
        $query['search'] = $search;
    }

    if ($status !== '') {
        $query['status'] = $status;
    }

    if ($dateFilter !== '') {
        $query['date_filter'] = $dateFilter;
    }

    if ($dateValue !== '') {
        $query['date_value'] = $dateValue;
    }

    return '?' . http_build_query($query);
}

function admin_case_pagination_pages(int $currentPage, int $totalPages): array
{
    if ($totalPages <= 7) {
        return range(1, max(1, $totalPages));
    }

    $pages = [1];
    $start = max(2, $currentPage - 1);
    $end = min($totalPages - 1, $currentPage + 1);

    if ($start > 2) {
        $pages[] = 'ellipsis-start';
    }

    for ($page = $start; $page <= $end; $page++) {
        $pages[] = $page;
    }

    if ($end < $totalPages - 1) {
        $pages[] = 'ellipsis-end';
    }

    $pages[] = $totalPages;

    return $pages;
}

function admin_case_selected(string $current, string $value): string
{
    return $current === $value ? ' selected' : '';
}
