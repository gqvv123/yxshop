<?php

namespace app\api\v1\controller;

use app\api\BaseController as ApiBaseController;

class BaseController extends ApiBaseController
{
    // getCurrentUser / getCurrentUserId 全部走父类（已对齐 JwtAuthMiddleware 写入的 $request->user）
}
