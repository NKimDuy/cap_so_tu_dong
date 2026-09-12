<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Ngoại lệ cho các vi phạm quy tắc nghiệp vụ (vd: vượt giới hạn 3 số chưa trả).
 * Message được viết bằng tiếng Việt để hiển thị thẳng ra giao diện.
 */
class BusinessRuleException extends RuntimeException
{
    //
}
