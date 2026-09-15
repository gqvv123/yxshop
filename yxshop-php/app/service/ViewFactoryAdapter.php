<?php

namespace app\service;

use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Jenssegers\Blade\Blade;

class ViewFactoryAdapter implements ViewFactoryContract
{
    protected $blade;

    public function __construct(Blade $blade)
    {
        $this->blade = $blade;
    }

    public function exists($view)
    {
        // 简单实现，总是返回 true
        return true;
    }

    public function file($path, $data = [], $mergeData = [])
    {
        // 不支持文件路径渲染
        return $this->make('dummy', $data, $mergeData);
    }

    public function make($view, $data = [], $mergeData = [])
    {
        // 在 Webman 中，我们只需要返回一个可以渲染的对象
        return new class($this->blade, $view, array_merge($data, $mergeData)) {
            private $blade;
            private $view;
            private $data;

            public function __construct($blade, $view, $data)
            {
                $this->blade = $blade;
                $this->view = $view;
                $this->data = $data;
            }

            public function render()
            {
                return $this->blade->render($this->view, $this->data);
            }
            
            public function __toString()
            {
                return $this->render();
            }
        };
    }

    public function share($key, $value = null)
    {
        // 不实现共享数据功能
        return $this;
    }

    public function composer($views, $callback)
    {
        // 不实现视图合成器功能
        return [];
    }

    public function creator($views, $callback)
    {
        // 不实现视图创建器功能
        return [];
    }

    public function addNamespace($namespace, $hints)
    {
        // 不实现命名空间功能
        return $this;
    }

    public function replaceNamespace($namespace, $hints)
    {
        // 不实现命名空间替换功能
        return $this;
    }
}