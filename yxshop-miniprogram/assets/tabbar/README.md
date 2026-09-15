# tabbar 图标说明

## 当前方案

小程序使用 **自定义 tabbar**（`custom: true`），位于 `custom-tab-bar/` 目录。
图标使用 `mp-icon` 矢量图标组件，**无需 PNG 图片文件**。

后台 TabBar 配置中 `icon_path` / `selected_icon_path` 留空时，自动使用内置矢量图标。
如果上传了自定义图片 URL，则优先使用图片。

## 内置矢量图标映射

| 页面路径 | 图标名 |
|---------|--------|
| `/pages/home/home` | `home` |
| `/pages/category/category` | `grid` |
| `/pages/cart/cart` | `shopping-cart` |
| `/pages/favorite/favorite` | `star` |
| `/pages/profile/profile` | `user-circle` |

## 颜色配置（对照设计稿）

| 属性 | 色值 | 说明 |
|------|------|------|
| 文字默认色 `color` | `#94a3b8` | 对应设计稿 `--text-3` |
| 文字选中色 `selected_color` | `#2563eb` | 对应设计稿 `--primary`（企业蓝） |
| 背景色 `background_color` | `#FFFFFF` | 白色背景 |
| 购物车凸起渐变 | `#2563eb → #3b82f6` | 对应设计稿 `--primary → --primary-light` |
| 角标背景 | `#dc2626` | 对应设计稿 `--danger` |

## app.json 中的 tabBar

`app.json` 中的 `tabBar.list` 仅为占位（微信要求至少 2 项），
实际渲染由 `custom-tab-bar/` 组件控制，配置来自后台数据库。

## 如需使用 PNG 图标

- 尺寸：建议 81×81 或 90×90 px (PNG)
- 容量：每个文件 ≤ 40KB
- 普通态用灰色描边图标，激活态用主色 `#2563eb` 实色图标
- 在后台 TabBar 配置页面上传图标即可
