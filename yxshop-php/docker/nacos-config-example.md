# Nacos 配置示例

## 配置说明

本系统使用 Nacos 作为配置中心，支持动态配置管理和服务发现。以下是各个服务的配置示例。

## 1. yxshop-php.yml (API服务配置)

```yaml
# 在 Nacos 中创建配置
# Data ID: yxshop-php.yml
# Group: DEFAULT_GROUP
# 配置格式: YAML

app:
  name: yxshop-php
  version: 1.0.0
  description: 基于Webman的电商系统API
  debug: false
  env: production
  timezone: Asia/Shanghai

server:
  listen: 0.0.0.0:8787
  processes: 4
  reloadable: true
  max_request: 1000000
  max_package_size: 10M

nacos:
  server_addr: localhost:8848
  namespace: yxshop
  group: DEFAULT_GROUP
  config:
    data_id: yxshop-php.yml
    group: DEFAULT_GROUP
    refresh: true
  discovery:
    service_name: yxshop-php
    group: DEFAULT_GROUP
    weight: 1
    enabled: true

database:
  default: mysql
  connections:
    mysql:
      driver: mysql
      host: mysql
      port: 3306
      database: yxshop
      username: yxshop
      password: secret
      charset: utf8mb4
      collation: utf8mb4_unicode_ci
      prefix: ''
      strict: true
      engine: InnoDB

redis:
  default:
    host: redis
    port: 6379
    password: ''
    database: 0
    timeout: 5.0
    retry_interval: 100
    read_timeout: 0
    context: null

logging:
  level:
    root: INFO
    com.yxshop: DEBUG
  pattern:
    console: "%d{yyyy-MM-dd HH:mm:ss.SSS} [%thread] %-5level %logger{50} - %msg%n"
    file: "%d{yyyy-MM-dd HH:mm:ss.SSS} [%thread] %-5level %logger{50} - %msg%n"
  file:
    name: /app/runtime/logs/yxshop-php.log
    max-size: 100MB
    max-history: 30
```

## 2. yxshop-admin.yml (后台管理配置)

```yaml
# 在 Nacos 中创建配置
# Data ID: yxshop-admin.yml
# Group: DEFAULT_GROUP
# 配置格式: YAML

app:
  name: yxshop-admin
  version: 1.0.0
  description: 基于Webman的电商系统后台管理
  debug: false
  env: production
  timezone: Asia/Shanghai

server:
  listen: 0.0.0.0:8788
  processes: 2
  reloadable: true
  max_request: 1000000
  max_package_size: 10M

nacos:
  server_addr: localhost:8848
  namespace: yxshop
  group: DEFAULT_GROUP
  config:
    data_id: yxshop-admin.yml
    group: DEFAULT_GROUP
    refresh: true
  discovery:
    service_name: yxshop-admin
    group: DEFAULT_GROUP
    weight: 1
    enabled: true

api:
  base_url: http://yxshop-php:8787
  timeout: 30
  retry_times: 3

yxshop:
  admin:
    title: YXShop后台管理系统
    logo: /admin/assets/images/logo.png
    favicon: /admin/assets/images/favicon.ico
    theme: default
    layout: sidebar
    
  permission:
    super_admin_role: 1
    default_permissions:
      - dashboard.view
      - profile.view
      - profile.edit
      
  pagination:
    default_per_page: 20
    max_per_page: 100
    
  upload:
    max_size: 10M
    allowed_types:
      - jpg
      - jpeg
      - png
      - gif
      - pdf
      - doc
      - docx
      - xls
      - xlsx
    path: /admin/uploads
```

## 3. yxshop-h5.yml (H5端配置)

```yaml
# 在 Nacos 中创建配置
# Data ID: yxshop-h5.yml
# Group: DEFAULT_GROUP
# 配置格式: YAML

app:
  name: yxshop-h5
  version: 1.0.0
  description: 基于Webman的电商系统H5移动端
  debug: false
  env: production
  timezone: Asia/Shanghai

server:
  listen: 0.0.0.0:8789
  processes: 2
  reloadable: true
  max_request: 1000000
  max_package_size: 10M

nacos:
  server_addr: localhost:8848
  namespace: yxshop
  group: DEFAULT_GROUP
  config:
    data_id: yxshop-h5.yml
    group: DEFAULT_GROUP
    refresh: true
  discovery:
    service_name: yxshop-h5
    group: DEFAULT_GROUP
    weight: 1
    enabled: true

api:
  base_url: http://yxshop-php:8787
  timeout: 30
  retry_times: 3

yxshop:
  h5:
    title: YXShop商城
    description: 专业的电商购物平台
    keywords: 商城,购物,电商,优惠
    theme: default
    layout: mobile
    
  wechat:
    app_id: your_wechat_app_id
    app_secret: your_wechat_app_secret
    jsapi_ticket_cache_time: 7200
    
  share:
    title: YXShop商城
    desc: 专业的电商购物平台
    link: http://localhost/h5
    imgUrl: http://localhost/h5/assets/images/share.jpg
    
  pagination:
    default_per_page: 20
    max_per_page: 50
    
  cache:
    home_data_ttl: 300
    product_list_ttl: 600
    product_detail_ttl: 1800
```

## 4. yxshop-common.yml (公共配置)

```yaml
# 在 Nacos 中创建配置
# Data ID: yxshop-common.yml
# Group: COMMON_GROUP
# 配置格式: YAML

yxshop:
  app:
    name: YXShop商城系统
    version: 1.0.0
    description: 基于Webman的电商系统
    author: YXShop Team
    website: https://www.yxshop.com
    
  version:
    default: basic
    pricing:
      basic:
        name: 开源版
        price: 0
        features:
          - user_management
          - product_management
          - order_management
          - inventory_management
          - payment_management
          - basic_coupon
          - basic_discount
          - basic_promotion
      professional:
        name: 进阶版
        price: 2999
        features:
          - advanced_coupon
          - advanced_discount
          - promotion_management
          - points_system
          - order_batch
          - inventory_warning
          - user_analytics
          - notification_system
          - basic_analytics
      enterprise:
        name: 高级版
        price: 9999
        features:
          - distribution_system
          - multi_warehouse
          - advanced_logistics
          - after_sales_management
          - review_management
          - miniprogram_management
          - system_management
          - advanced_analytics
          - multi_tenant
          - multi_language
          - api_management

  payment:
    wechat:
      app_id: your_wechat_app_id
      app_secret: your_wechat_app_secret
      mch_id: your_wechat_mch_id
      key: your_wechat_key
      notify_url: http://localhost/api/payment/wechat/notify
      return_url: http://localhost/payment/return
    alipay:
      app_id: your_alipay_app_id
      private_key: your_alipay_private_key
      public_key: your_alipay_public_key
      notify_url: http://localhost/api/payment/alipay/notify
      return_url: http://localhost/payment/return

  sms:
    driver: aliyun
    aliyun:
      access_key_id: your_aliyun_access_key_id
      access_key_secret: your_aliyun_access_key_secret
      sign_name: YXShop
      template_code: your_template_code

  mail:
    driver: smtp
    host: smtp.qq.com
    port: 587
    username: your_email@qq.com
    password: your_email_password
    encryption: tls
    from:
      address: your_email@qq.com
      name: YXShop

  storage:
    driver: local
    local:
      root: /app/public/uploads
    oss:
      access_key_id: your_aws_access_key_id
      access_key_secret: your_aws_secret_access_key
      bucket: your_bucket_name
      region: your_region

  monitoring:
    sentry:
      dsn: your_sentry_dsn
      traces_sample_rate: 0.1
    prometheus:
      enabled: true
      port: 9090
      path: /metrics

  security:
    jwt:
      secret: yxshop_jwt_secret_key_change_in_production
      ttl: 7200
      refresh_ttl: 604800
    rate_limit:
      api: 100
      login: 5
      register: 3
    cors:
      allowed_origins: "*"
      allowed_methods: "GET,POST,PUT,DELETE,OPTIONS"
      allowed_headers: "Content-Type,Authorization,X-Requested-With"
```

## 5. 环境变量配置

在 Docker Compose 中，仍然需要设置一些基础环境变量：

```yaml
# .env 文件
MYSQL_DATABASE=yxshop
MYSQL_USER=yxshop
MYSQL_PASSWORD=secret
MYSQL_ROOT_PASSWORD=rootpass
REDIS_PASSWORD=

# Nacos 配置
NACOS_SERVER_ADDR=localhost:8848
NACOS_NAMESPACE=yxshop
NACOS_GROUP=DEFAULT_GROUP

# 微信配置
WECHAT_APP_ID=your_wechat_app_id
WECHAT_APP_SECRET=your_wechat_app_secret
WECHAT_MCH_ID=your_wechat_mch_id
WECHAT_KEY=your_wechat_key

# 支付宝配置
ALIPAY_APP_ID=your_alipay_app_id
ALIPAY_PRIVATE_KEY=your_alipay_private_key
ALIPAY_PUBLIC_KEY=your_alipay_public_key

# 短信配置
SMS_ALIYUN_ACCESS_KEY_ID=your_aliyun_access_key_id
SMS_ALIYUN_ACCESS_KEY_SECRET=your_aliyun_access_key_secret
SMS_ALIYUN_SIGN_NAME=YXShop
SMS_ALIYUN_TEMPLATE_CODE=your_template_code

# 邮件配置
MAIL_HOST=smtp.qq.com
MAIL_PORT=587
MAIL_USERNAME=your_email@qq.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@qq.com
MAIL_FROM_NAME=YXShop

# 其他配置
JWT_SECRET=yxshop_jwt_secret_key_change_in_production
SENTRY_LARAVEL_DSN=your_sentry_dsn
SENTRY_TRACES_SAMPLE_RATE=0.1
```

## 6. 使用说明

1. **启动 Nacos 服务**：
   ```bash
   # 下载并启动 Nacos
   docker run -d --name nacos -p 8848:8848 -e MODE=standalone nacos/nacos-server:latest
   ```

2. **访问 Nacos 控制台**：
   - 地址：http://localhost:8848/nacos
   - 用户名：nacos
   - 密码：nacos

3. **创建命名空间**：
   - 在 Nacos 控制台中创建名为 `yxshop` 的命名空间

4. **导入配置**：
   - 将上述 YAML 配置分别导入到对应的 Data ID 中
   - 确保 Group 设置正确

5. **启动服务**：
   ```bash
   cd yxshop-php/docker
   docker-compose up -d
   ```

## 7. 配置热更新

当在 Nacos 中修改配置后，服务会自动检测到配置变化并重新加载，无需重启服务。这大大提高了系统的灵活性和可维护性。
