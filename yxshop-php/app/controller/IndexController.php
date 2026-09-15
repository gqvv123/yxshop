<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\service\CurrencyService;
use app\service\LanguageService;
use app\exception\NotFoundException;

class IndexController extends BaseController
{
    protected $currencyService;
    protected $languageService;

    public function __construct()
    {
        $this->currencyService = new CurrencyService();
        $this->languageService = new LanguageService();
    }

    public function index(Request $request)
    {
        return 'Hello World';
    }

    public function json(Request $request)
    {
        return $this->success(null, 'ok');
    }

    public function changeCurrency(Request $request, string $code)
    {
        $currency = $this->currencyService->getByCode($code);
        if (!$currency) {
            throw new NotFoundException('货币不存在');
        }

        $request->session()->put('currency', $code);
        $request->session()->put('web_currency', $currency);

        if ($request->expectsJson() || $request->isAjax()) {
            return $this->success(['currency' => $code], '货币切换成功');
        }

        $referer = $request->header('referer', '/');
        return redirect($referer);
    }

    public function changeLanguage(Request $request, string $code)
    {
        $language = $this->languageService->getByCode($code);
        if (!$language) {
            throw new NotFoundException('语言不存在');
        }

        $request->session()->put('locale', $code);

        if ($request->expectsJson() || $request->isAjax()) {
            return $this->success(['language' => $code], '语言切换成功');
        }

        $referer = $request->header('referer', '/');
        return redirect($referer);
    }
}
