<?php

namespace app\common;

class Status {

    const ENABLE = 1;
    const DISABLE = 0;

    const YES = 1;
    const NO = 0;

    const VERIFIED = 1;
    const UNVERIFIED = 0;

    // 订单状态
    const ORDER_PENDING = 10;
    const ORDER_CANCELLED = 20;
    const ORDER_COMPLETED = 30;
    const ORDER_REFUNDED = 40;
    const ORDER_REFUND_PENDING = 50;
    const ORDER_REFUND_APPROVED = 60;
    const ORDER_REFUND_REJECTED = 70;
    const ORDER_REFUND_CANCELLED = 80;

    const PAYMENT_INITIATE = 0;
    const PAYMENT_SUCCESS = 1;
    const PAYMENT_PENDING = 2;
    const PAYMENT_REJECT = 3;
    const PAYMENT_CANCEL = 4;
    const PAYMENT_CLOSED = 5;
    const PAYMENT_CANCELED = 9;

    const TICKET_OPEN = 0;
    const TICKET_ANSWER = 1;
    const TICKET_REPLY = 2;
    const TICKET_CLOSE = 3;

    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;

    const USER_ACTIVE = 1;
    const USER_BAN = 0;
}
