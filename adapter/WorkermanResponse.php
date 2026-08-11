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

    /**
     * Workerman 下不能走父类 sendFile：
     * 父类会 ob_end_clean 掉 WorkermanApp::run() 的缓冲区，且 echo 进不了 Response body。
     * 改用协议层 withFile，由 Http::encode 流式读盘发送。
     */
    protected function sendFile(): void
    {
        if ($this->code === 404 || !is_string($this->data) || !is_file($this->data)) {
            $this->code = 404;
            $this->sendHeaders();
            echo is_string($this->data) && $this->data !== '' ? $this->data : 'File not found';
            return;
        }

        $fileSize = filesize($this->data);
        if ($fileSize === false) {
            $this->code = 404;
            $this->sendHeaders();
            echo 'File not found';
            return;
        }

        $offset = 0;
        $length = 0; // 0 = 从 offset 读到文件末尾（Workerman 约定）
        $range = $this->parseRange($fileSize);
        if ($range !== null) {
            [$start, $end] = $range;
            $offset = $start;
            $length = $end - $start + 1;
            $this->code = 206;
            $this->header['Content-Range'] = "bytes $start-$end/$fileSize";
            $this->header['Content-Length'] = $length;
        } else {
            $this->code = 200;
            $this->header['Content-Length'] = $fileSize;
        }

        $this->sendHeaders();
        if ($this->isHead()) {
            return;
        }

        WorkermanApp::instance()->response()
            ->withStatus($this->code)
            ->withFile($this->data, $offset, $length);
    }
}
