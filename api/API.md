# 支付中心 API 文档

本接口文档基于 `api/` 目录下的接口源码自动生成，当前支持**微信支付**与**通联聚合支付**相关接口，覆盖扫码下单、聚合收银台、查单、回调业务通知等完整流程，适用于 SaaS/B端业务系统集成。

---

## 1. 创建微信支付订单

**接口地址**  
`POST /api/wechat_create_order.php`

**请求头**  
`Content-Type: application/json`

**请求参数（JSON Body）**

| 参数名        | 类型   | 必填 | 说明         |
|:-------------|:------|:-----|:-------------|
| biz_sys      | string| 是   | 业务系统标识，如 books |
| biz_order_no | string| 是   | 业务订单号（需唯一）|
| user_id      | int   | 是   | 用户ID       |
| amount       | float | 是   | 金额（元）   |
| subject      | string| 是   | 商品名称     |
| notify_url   | string| 否   | 业务系统回调URL（推荐填写）|
| extra        | object| 否   | 额外扩展字段，任意JSON对象 |

**请求示例**
```json
{
  "biz_sys": "books",
  "biz_order_no": "WX202406051200009999",
  "user_id": 12345,
  "amount": 199.00,
  "subject": "SaaS平台套餐购买-专业版",
  "notify_url": "https://b.wsx.tax/pay_notify.php",
  "extra": { "plan": "pro" }
}
```

**返回参数（JSON）**

成功时：
```json
{
  "code": 0,
  "msg": "ok",
  "data": {
    "pay_order_no": "202406051234567890",
    "qrcode_url": "weixin://wxpay/bizpayurl?pr=xxxx",
    "pay_url": "https://pay.wsx.tax/wechat/pay/202406051234567890"
  }
}
```
失败时：
```json
{
  "code": 1,
  "msg": "错误信息"
}
```

**说明**  
- 下单成功后，返回微信扫码二维码链接和支付中心跳转链接。
- 同一业务订单号不可重复下单。
- **所有接口均支持白名单IP校验，生产环境请联系管理员添加业务服务器IP。**

---

## 2. 创建通联聚合支付订单

**接口地址**  
`POST /api/allinpay_create_order.php`

**请求头**  
`Content-Type: application/json`

**请求参数（JSON Body）**

| 参数名        | 类型   | 必填 | 说明         |
|:-------------|:------|:-----|:-------------|
| biz_sys      | string| 是   | 业务系统标识，如 books |
| biz_order_no | string| 是   | 业务订单号（需唯一）|
| user_id      | int   | 是   | 用户ID       |
| amount       | float | 是   | 金额（元）   |
| subject      | string| 是   | 商品名称     |
| paytype      | string| 是   | 支付方式ID（W01:微信，A01:支付宝，U01:云闪付）|
| notify_url   | string| 否   | 业务系统回调URL（推荐填写）|
| return_url   | string| 否   | 支付完成后跳转URL |
| extra        | object| 否   | 额外扩展字段，任意JSON对象 |

**请求示例**
```json
{
  "biz_sys": "books",
  "biz_order_no": "ALLIN202406061626009999",
  "user_id": 12345,
  "amount": 99.00,
  "subject": "SaaS套餐购买-标准版",
  "paytype": "W01",
  "notify_url": "https://b.wsx.tax/pay_notify.php",
  "return_url": "https://b.wsx.tax/pay_result.php",
  "extra": { "plan": "standard" }
}
```

**返回参数（JSON）**

成功时：
```json
{
  "code": 0,
  "msg": "ok",
  "data": {
    "pay_order_no": "202506061626000001",
    "qrcode_url": "https://pay.wsx.tax/qrcode?data=xxxx",
    "pay_url": "https://pay.wsx.tax/allinpay/qrcode.php?pay_order_no=202506061626000001"
  }
}
```
失败时：
```json
{
  "code": 1,
  "msg": "错误信息"
}
```

**说明**  
- 同一业务订单号不可重复下单，已存在则直接返回原订单号和二维码。
- qrcode_url 为聚合扫码链接，pay_url为聚合收银台页面。
- **支持多种扫码方式，支付方式列表可通过 `/api/allinpay_types.php` 动态获取。**

---

## 3. 查询订单支付状态

### 微信支付

**接口地址**  
`GET /api/wechat_query_order.php?pay_order_no=xxxx`

### 通联聚合支付

**接口地址**  
`GET /api/allinpay_query_order.php?pay_order_no=xxxx`

**请求参数（Query）**

| 参数名        | 类型   | 必填 | 说明           |
|:-------------|:------|:-----|:---------------|
| pay_order_no | string| 是   | 支付订单号     |

**返回参数（JSON）**
```json
{
  "code": 0,
  "status": "paid",      // 或 "unpaid"
  "paid_at": "2025-06-06 16:30:00"
}
```
- code = 0 表示成功，status字段为订单状态，paid_at为支付时间（未支付为空）。
- code ≠ 0 表示失败，msg为错误信息。

---

## 4. 获取可用聚合支付方式

**接口地址**  
`GET /api/allinpay_types.php`

**返回参数（JSON）**
```json
{
  "code": 0,
  "msg": "ok",
  "pay_types": [
    {"id": "W01", "name": "微信扫码"},
    {"id": "A01", "name": "支付宝扫码"},
    {"id": "U01", "name": "云闪付扫码"}
  ]
}
```
---

## 5. 业务系统回调参数说明（notify_url POST回调）

当订单支付成功时，支付中心会向下单时指定的 `notify_url` 发送如下 POST 参数：

| 参数名        | 说明         |
|:-------------|:-------------|
| biz_order_no | 业务订单号   |
| pay_order_no | 支付订单号   |
| user_id      | 用户ID       |
| amount       | 金额（2位小数，字符串）|
| status       | 固定为 "paid"|
| paid_at      | 支付时间     |
| sign         | 签名，md5(biz_order_no + pay_order_no + user_id + amount + secret) |

**签名校验示例（PHP）：**
```php
$secret = getenv('PAYMENT_NOTIFY_SECRET') ?: '';
    if (strlen($secret) < 32) { throw new RuntimeException('Configure PAYMENT_NOTIFY_SECRET (at least 32 characters) on both systems.'); } // 与支付中心保持一致
$expected_sign = md5($biz_order_no.$pay_order_no.$user_id.$amount.$secret);
if ($sign !== $expected_sign) {
    http_response_code(403);
    echo "签名错误";
    exit;
}
// ... 业务逻辑 ...
echo "OK";
```

**业务系统回调处理建议：**
- 校验签名正确
- 校验订单号、金额与本地一致
- 已支付订单多次回调须幂等处理
- 业务处理成功后，返回字符串 `OK`，否则返回错误信息

---

## 6. 业务系统对接示例

以 [package_buy.php](https://github.com/lanbing1989/b.wsx.tax/blob/main/package_buy.php) 和 [pay_notify.php](https://github.com/lanbing1989/b.wsx.tax/blob/main/pay_notify.php) 为例，完整流程如下：

### 6.1 业务系统下单流程

1. 用户在业务系统选择套餐，点击“订购/升级”。
2. 业务系统生成本地业务订单，调用 `/api/wechat_create_order.php` 或 `/api/allinpay_create_order.php` 创建支付订单。
3. 获取二维码、pay_url并跳转聚合收银台或自行渲染二维码。

**示例代码片段（PHP）**
```php
$payload = [
    'biz_sys'      => 'books',
    'biz_order_no' => $order_no,
    'user_id'      => $user_id,
    'amount'       => $amount,
    'subject'      => $subject,
    'paytype'      => 'W01',
    'notify_url'   => $notify_url,
    'return_url'   => $return_url,
    'extra'        => [ 'plan' => $plan ]
];
$pay_api = 'https://pay.wsx.tax/api/allinpay_create_order.php';
$ch = curl_init($pay_api);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$pay_resp = curl_exec($ch);
curl_close($ch);
$pay_data = json_decode($pay_resp, true);
$qrcode_url = $pay_data['data']['qrcode_url'];
$pay_url = $pay_data['data']['pay_url'];
// 跳转收银台或渲染二维码
header("Location: $pay_url");
```

### 6.2 业务系统回调处理

1. 支付中心支付成功后，POST 通知业务系统的 `notify_url`，如 `/pay_notify.php`。
2. 业务系统校验签名、校验订单金额、更新订单状态和用户套餐，返回 `OK`。

---

## 7. 常见问题

- 若订单已支付但 notify_url 未收到回调，可在支付中心后台重试通知。
- 业务 notify_url 必须能公网访问，且响应 "OK" 表示成功。
- 所有 API 已增加白名单IP限制，需将业务系统出口IP加入白名单。
- 聚合收银台页面为 `https://pay.wsx.tax/allinpay/qrcode.php?pay_order_no=...`，可直接跳转。

---

> 如需更多接口或详细参数说明，请查看源码或联系开发者。
