<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

namespace nova\plugin\workerman\adapter;

use nova\framework\http\Response;

class WorkermanResponse extends Response
{
    public function sendSSE(): void
    {
        $callback = $this->data;
        $this->sendHeaders();
        if ($this->isHead()) {
            return;
        }
        WorkermanApp::instance()->sendResponse();
        $callback(function ($data, $event = null) {
            if ($data == null) {
                return;
            }
            WorkermanApp::instance()->sendSSE(['data' => $data, 'event' => $event ?? 'message']);
        });
        while (!connection_aborted()) {
            sleep(1);
        }
    }

    protected function sendFile(): void
    {

    }
}
