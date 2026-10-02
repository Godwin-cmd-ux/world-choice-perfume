<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\CustomerCare\InfoMailController as CustomerCareInfoMailController;

/**
 * Super Admin → Emails: the same info@worldchoiceperfume.com mailbox that
 * Customer Care reads, shown from the Super Admin side.
 *
 * This is deliberately not a second mailbox. It reuses the Customer Care
 * controller, which in turn uses the shared InfoMailService: the same stored
 * messages, the same attachments, the same reply transport and the same
 * templates. The only thing that differs is the route names the pages post
 * back to, so overriding that one prefix is the whole of the reuse.
 */
class InfoMailController extends CustomerCareInfoMailController
{
    protected function routePrefix(): string
    {
        return 'super-admin.emails';
    }
}
