<?php
require_once dirname(__DIR__, 2) . '/includes/functions.php';

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin_api(): void
{
    if (!admin_logged_in()) {
        json_error('Unauthorized', 401);
    }
}
