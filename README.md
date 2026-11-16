# WeChat-Unified-Payment-Center 统一支付中心

## 项目简介

`WeChat-Unified-Payment-Center` 是 SaaS 系列的统一支付中心模块，基于 PHP 开发，支持多业务系统对统一支付网关的接入。当前代码包含微信支付 NATIVE 和通联支付接口，具备标准的下单、支付回调、状态查询、业务通知等完整流程，便于对接多租户、微服务、SaaS 场景。

---

## 核心功能

- 多渠道支付（微信支付及通联支付；接口分别位于 `api/wechat_*.php` 和 `api/allinpay_*.php`）
- 统一订单号生成与管理
- 业务系统与支付系统单号一一映射
- 支付回调（notify），参数验签、订单校验、业务异步通知
- 订单状态实时查询
- 支付二维码/链接生成
- 管理后台（订单、渠道、配置管理）

---

## 目录结构简要

- `api/`                — 支付相关API接口
- `wechat/`             — 微信支付 SDK/接口适配
- `admin/`              — 管理后台
- `config.php`          — 支付参数配置
- `db.php`              — 数据库连接
- `install.sql`         — 初始化数据库脚本
- `utils.php`           — 工具函数
- `nginx.txt`           — Nginx 配置参考

---

## 安装与部署

### 1. 克隆代码

```bash
git clone https://github.com/Lucian-Wang/WeChat-Unified-Payment-Center.git
cd WeChat-Unified-Payment-Center
```

### 2. 初始化数据库

```bash
mysql -u root -p db < install.sql
```

### 3. 配置参数

- 编辑 `config.php`，填写数据库连接、微信商户号、API密钥、通知URL等参数。
- 微信支付相关证书和参数请放置于 `wechat/` 目录。

### 4. 配置 Nginx

- 参考 [`nginx.txt`](https://github.com/Lucian-Wang/WeChat-Unified-Payment-Center/blob/main/nginx.txt)。
- Web 根目录应指向项目根目录，保留 `/api/`、`/admin/` 和 `/wechat/` 路由；不要将 Web 根目录设成 `api/`。

### 5. 访问后台

- 管理后台路径为 `/admin`，用于查看和管理支付订单、通道配置等。

---

## 接口文档 & 调用流程

### 1. 创建微信支付订单

- **接口**：`POST /api/wechat_create_order.php`
- **请求参数（JSON）**：
    - `biz_sys`      （业务系统标识）
    - `biz_order_no` （业务系统订单号）
    - `user_id`      （用户ID）
    - `amount`       （金额/元，float）
    - `subject`      （商品/订单标题）
    - `notify_url`   （业务系统接收支付结果的回调URL）
    - `extra`        （扩展信息，可选，结构化数据）
- **返回**：
    ```json
    {
      "code": 0,
      "msg": "ok",
      "data": {
        "pay_order_no": "2025060512345678123",
        "qrcode_url": "https://wxpay.qrcode.url/...",
        "pay_url": "https://pay.wsx.tax/wechat/pay/2025060512345678123"
      }
    }
    ```
- **业务说明**：
    - 所有参数均为必填（除 extra）
    - 同一业务单号禁止重复下单
    - 返回二维码链接与本地支付链接

### 2. 查询订单支付状态

- **接口**：`GET /api/wechat_query_order.php?pay_order_no=XXX`
- **参数**：`pay_order_no`（支付中心生成的支付单号）
- **返回**：
    ```json
    { "code": 0, "status": "paid", "paid_at": "2025-06-05 10:23:11" }
    ```
- **业务说明**：
    - 状态包括 `unpaid`（未支付）、`paid`（已支付）

### 3. 支付回调（notify）

- **接口**：`POST /api/wechat_notify.php`
- **触发方式**：由微信支付服务器回调（XML）
- **主要流程**：
    - 验证签名（防止伪造）
    - 校验订单、金额、状态，重复回调自动幂等
    - 更新订单为已支付，记录 raw 回调数据
    - 调用业务系统 notify_url，POST 业务订单号、支付单号、状态、金额、签名等通知参数（sign=md5(biz_order_no+pay_order_no+user_id+amount+secret)）
    - 业务系统返回 OK 即认为通知成功

### 4. 业务系统回调处理

- **参数**：
    - `biz_order_no` 业务单号
    - `pay_order_no` 支付中心单号
    - `user_id`
    - `amount`
    - `status`（paid）
    - `paid_at`
    - `sign`
- **验签方式**：`md5(biz_order_no+pay_order_no+user_id+amount+secret)`
- **建议**：业务系统需二次验签、校验金额和单号，防止伪通知

---

## 订单与支付数据表

- `pay_orders`：存储所有支付订单（含业务系统单号、用户、金额、二维码、回调信息、通知状态等）
- 其他表可扩展实现（如支付日志、渠道配置等）

---

## 管理后台

- 支持订单查询、状态管理、渠道参数配置、日志查看等功能
- 多角色多权限支持，便于财务/运营/技术分工

---

## 常见问题与排查

- **下单失败**：检查参数完整性、数据库连接、微信配置
- **回调未收到**：检查 notify_url 配置、服务器防火墙、微信商户平台配置
- **重复回调**：系统幂等处理，业务系统需校验防止重复处理订单
- **订单状态不更新**：检查数据库写入、回调流程、日志

---

## 二次开发建议

- 新增支付渠道，只需参照 wechat 模块实现渠道适配函数、回调接口
- 业务逻辑高度分层，便于快速扩展和维护
- 推荐为所有接口增加 Token 或签名校验，提高安全性
- 前后端接口建议采用 HTTPS

---

## 参与贡献

1. Fork 本仓库
2. 新建分支
3. 提交 Pull Request
4. Issue 区反馈建议/bug

---

## 许可协议

MIT License

---

> 更多详细开发说明、接口协议和参数解释，请查阅源码及注释，或联系维护者。

## 维护与反馈

维护者：[Lucian](https://lucian.wang/) · 邮箱：im@lucian.wang · [问题反馈](https://github.com/Lucian-Wang/WeChat-Unified-Payment-Center/issues)


## 部署数据与凭据

SQL 已去除支付订单。商户私钥不随源码分发；在自己的部署机器上配置程序所需的商户私钥，并限制文件权限，切勿提交 Git。已公开的商户私钥必须在支付服务商处撤销或换绑。数据库和支付参数在本机 config.php 设置。

业务通知签名使用环境变量 `PAYMENT_NOTIFY_SECRET`（至少 32 字符的独有随机值），支付中心与接收通知的业务系统必须设置相同的值；未配置时拒绝签名。不要复用旧共享密钥。
