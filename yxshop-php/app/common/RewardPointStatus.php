<?php

namespace app\common;

enum RewardPointStatus: int
{
    case PENDING = 0;
    case REDEEMED = 1;
    case EXPIRED = 2;
}

