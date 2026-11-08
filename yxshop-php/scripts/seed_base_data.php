<?php
/**
 * 基础数据填充脚本（幂等，可重复执行）。
 *
 * 背景：本地库的各基础表是压测遗留的空壳数据（name 为空、关联表为空），
 * 导致 C 端新接口（标签/配送/地区/促销/广告）返回空内容，无法验收。
 * 本脚本把「地区、快递公司、配送模板+计费规则、商品类型、商品属性、
 * 促销活动、广告位」补齐为真实可用数据。
 *
 * 幂等策略：
 *   - 有名称的行 → 跳过（已填充过）
 *   - 空名称的行 → 就地补全（复用已有 id，不破坏外键引用）
 *   - 关联表（delivery_rules / item_tag_relations）→ 按需重建
 *
 * 用法：php scripts/seed_base_data.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * 独立运行（不经 webman 运行时）时手动建立连接。
 * 读取项目 .env 的数据库配置，避免依赖 config() 助手函数。
 */
function bootstrapDb(): Capsule
{
    $root = dirname(__DIR__);
    $env = [];
    if (is_file($root . '/.env')) {
        foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            $env[trim($k)] = trim($v, " \t\"'");
        }
    }

    $capsule = new Capsule(new Container());
    $capsule->addConnection([
        'driver'   => 'mysql',
        'host'     => $env['DB_HOST'] ?? '127.0.0.1',
        'port'     => $env['DB_PORT'] ?? '3306',
        'database' => $env['DB_DATABASE'] ?? 'yxshop',
        'username' => $env['DB_USERNAME'] ?? 'root',
        'password' => $env['DB_PASSWORD'] ?? '',
        'charset'  => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'   => '',
        'strict'   => true,
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
}

$capsule = bootstrapDb();

/** 便捷的表查询入口 */
function table(string $name)
{
    global $capsule;
    return $capsule->getConnection()->table($name);
}

/** 雪花 ID 生成（与项目一致的粗略实现：时间戳 + 自增） */
$idSeed = 0;
function newId(): string
{
    static $seq = 0;
    $seq++;
    // 与已有 id 保持同量级（19 位），避免与现有数据混淆
    return (string) (376900000000000000 + (int) (microtime(true) * 1000) * 100 + $seq);
}

function out(string $msg): void
{
    echo $msg . PHP_EOL;
}

/* ============================================================
 * 1. 地区：省(pid=0,level=1) → 市(pid=省id,level=2) → 区(pid=市id,level=3)
 * ============================================================ */
$provinces = [
    '北京市'   => ['北京市' => ['东城区', '西城区', '朝阳区', '海淀区', '丰台区']],
    '上海市'   => ['上海市' => ['黄浦区', '徐汇区', '长宁区', '静安区', '浦东新区']],
    '广东省'   => ['广州市' => ['天河区', '越秀区', '海珠区', '白云区', '番禺区'],
                   '深圳市' => ['福田区', '南山区', '罗湖区', '宝安区', '龙岗区'],
                   '东莞市' => ['莞城街道', '南城街道', '东城街道', '万江街道', '松山湖']],
    '浙江省'   => ['杭州市' => ['西湖区', '拱墅区', '滨江区', '余杭区', '萧山区'],
                   '宁波市' => ['海曙区', '江北区', '鄞州区', '镇海区', '北仑区']],
    '江苏省'   => ['南京市' => ['玄武区', '秦淮区', '建邺区', '鼓楼区', '浦口区'],
                   '苏州市' => ['姑苏区', '虎丘区', '吴中区', '相城区', '吴江区']],
    '四川省'   => ['成都市' => ['锦江区', '青羊区', '金牛区', '武侯区', '高新区']],
    '湖北省'   => ['武汉市' => ['江岸区', '江汉区', '硚口区', '武昌区', '洪山区']],
    '陕西省'   => ['西安市' => ['新城区', '碑林区', '莲湖区', '雁塔区', '未央区']],
    '山东省'   => ['济南市' => ['历下区', '市中区', '槐荫区', '天桥区', '历城区'],
                   '青岛市' => ['市南区', '市北区', '李沧区', '崂山区', '城阳区']],
    '福建省'   => ['福州市' => ['鼓楼区', '台江区', '仓山区', '马尾区', '晋安区'],
                   '厦门市' => ['思明区', '海沧区', '湖里区', '集美区', '同安区']],
];

$provinceRows = table('yxshop_regions')->where('pid', 0)->get();
$provinceMap = [];
foreach ($provinceRows as $row) {
    if (!empty($row->name)) {
        $provinceMap[$row->name] = (string) $row->id;
    }
}

// 已存在省份 → 直接复用；否则新建
foreach (array_keys($provinces) as $provinceName) {
    if (isset($provinceMap[$provinceName])) {
        continue;
    }
    $id = newId();
    table('yxshop_regions')->insert([
        'id'           => $id,
        'pid'          => 0,
        'name'         => $provinceName,
        'shortname'    => $provinceName,
        'merger_name'  => $provinceName,
        'level'        => 1,
        'pinyin'       => '',
        'code'         => '',
        'zip_code'     => '',
        'first'        => mb_substr($provinceName, 0, 1),
        'lng'          => '',
        'lat'          => '',
    ]);
    $provinceMap[$provinceName] = $id;
    out("  [地区] 新增省份 {$provinceName}");
}

// 找出空的省份行（有 id 无名称），补上名称
$emptyProvinces = table('yxshop_regions')->where('pid', 0)->where(function ($q) {
    $q->whereNull('name')->orWhere('name', '');
})->get();
$provinceNames = array_keys($provinces);
foreach ($emptyProvinces as $row) {
    $name = $provinceNames[0] ?? null;
    if ($name === null) {
        break;
    }
    table('yxshop_regions')->where('id', $row->id)->update([
        'name'        => $name,
        'shortname'   => $name,
        'merger_name' => $name,
        'level'       => 1,
        'first'       => mb_substr($name, 0, 1),
    ]);
    $provinceMap[$name] = (string) $row->id;
    unset($provinceNames[0]);
    $provinceNames = array_values($provinceNames);
    out("  [地区] 补全空省份行 → {$name}");
}

// 市 / 区
foreach ($provinces as $provinceName => $cities) {
    $provinceId = $provinceMap[$provinceName] ?? null;
    if (!$provinceId) {
        continue;
    }
    foreach ($cities as $cityName => $districts) {
        $cityId = table('yxshop_regions')
            ->where('pid', $provinceId)
            ->where('name', $cityName)
            ->value('id');

        if (!$cityId) {
            $cityId = newId();
            table('yxshop_regions')->insert([
                'id'          => $cityId,
                'pid'         => $provinceId,
                'name'        => $cityName,
                'shortname'   => $cityName,
                'merger_name' => $provinceName . ',' . $cityName,
                'level'       => 2,
                'first'       => mb_substr($cityName, 0, 1),
                'pinyin'      => '',
                'code'        => '',
                'zip_code'    => '',
                'lng'         => '',
                'lat'         => '',
            ]);
            out("  [地区] 新增城市 {$provinceName}/{$cityName}");
        }

        foreach ($districts as $districtName) {
            $exists = table('yxshop_regions')
                ->where('pid', $cityId)
                ->where('name', $districtName)
                ->value('id');
            if ($exists) {
                continue;
            }
            table('yxshop_regions')->insert([
                'id'          => newId(),
                'pid'         => $cityId,
                'name'        => $districtName,
                'shortname'   => $districtName,
                'merger_name' => $provinceName . ',' . $cityName . ',' . $districtName,
                'level'       => 3,
                'first'       => mb_substr($districtName, 0, 1),
                'pinyin'      => '',
                'code'        => '',
                'zip_code'    => '',
                'lng'         => '',
                'lat'         => '',
            ]);
        }
    }
}
out('  [地区] 完成：' . table('yxshop_regions')->count() . ' 条');

/* ============================================================
 * 2. 快递公司
 * ============================================================ */
$expressList = [
    ['name' => '顺丰速运', 'code' => 'SF',    'sort' => 1],
    ['name' => '中通快递', 'code' => 'ZTO',   'sort' => 2],
    ['name' => '圆通速递', 'code' => 'YTO',   'sort' => 3],
    ['name' => '韵达快递', 'code' => 'YD',    'sort' => 4],
    ['name' => '申通快递', 'code' => 'STO',   'sort' => 5],
    ['name' => '京东物流', 'code' => 'JD',    'sort' => 6],
    ['name' => '邮政EMS',  'code' => 'EMS',   'sort' => 7],
    ['name' => '极兔速递', 'code' => 'JTSD',  'sort' => 8],
];

$now = time();
foreach ($expressList as $e) {
    $exists = table('yxshop_expresses')->where('code', $e['code'])->value('id');
    if ($exists) {
        table('yxshop_expresses')->where('id', $exists)->update([
            'name'   => $e['name'],
            'status' => 1,
            'app_id' => 0,
        ]);
        continue;
    }
    table('yxshop_expresses')->insert([
        'id'         => newId(),
        'name'       => $e['name'],
        'code'       => $e['code'],
        'sort'       => $e['sort'],
        'app_id'     => 0,
        'status'     => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}
out('  [快递] 完成：' . table('yxshop_expresses')->count() . ' 家');

/* ============================================================
 * 3. 配送模板 + 计费规则
 * ============================================================ */
$deliveryList = [
    ['name' => '全国包邮（默认）', 'method' => 10, 'sort' => 1,
     'rules' => [['region' => '', 'first' => 1, 'first_fee' => 0, 'additional' => 1, 'additional_fee' => 0,
                  'min_price' => 0, 'min_num' => 0, 'delivery_type' => 30]]],
    ['name' => '标准快递', 'method' => 10, 'sort' => 2,
     'rules' => [['region' => '', 'first' => 1, 'first_fee' => 10, 'additional' => 1, 'additional_fee' => 3,
                  'min_price' => 99, 'min_num' => 0, 'delivery_type' => 30]]],
    ['name' => '华东次日达', 'method' => 10, 'sort' => 3,
     'rules' => [
         ['region' => '', 'first' => 1, 'first_fee' => 8, 'additional' => 1, 'additional_fee' => 2,
          'min_price' => 199, 'min_num' => 0, 'delivery_type' => 20],
         ['region' => '[376900000000000000]', 'first' => 1, 'first_fee' => 12, 'additional' => 1, 'additional_fee' => 4,
          'min_price' => 199, 'min_num' => 0, 'delivery_type' => 20],
     ]],
    ['name' => '大件物流', 'method' => 20, 'sort' => 4,
     'rules' => [['region' => '', 'first' => 1, 'first_fee' => 30, 'additional' => 1, 'additional_fee' => 10,
                  'min_price' => 0, 'min_num' => 5, 'delivery_type' => 30]]],
];

foreach ($deliveryList as $d) {
    $deliveryId = table('yxshop_deliveries')->where('name', $d['name'])->value('id');
    if (!$deliveryId) {
        // 优先复用空名称行，避免产生冗余记录
        $emptyId = table('yxshop_deliveries')
            ->where(function ($q) {
                $q->whereNull('name')->orWhere('name', '');
            })->value('id');
        $deliveryId = $emptyId ?: newId();

        $payload = [
            'name'       => $d['name'],
            'method'     => $d['method'],
            'app_id'     => 0,
            'status'     => 1,
            'sort'       => $d['sort'],
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if ($emptyId) {
            table('yxshop_deliveries')->where('id', $emptyId)->update($payload);
        } else {
            $payload['id'] = $deliveryId;
            table('yxshop_deliveries')->insert($payload);
        }
    }

    // 规则：按 delivery_id 全量重建
    table('yxshop_delivery_rules')->where('delivery_id', $deliveryId)->delete();
    foreach ($d['rules'] as $rule) {
        table('yxshop_delivery_rules')->insert([
            'id'             => newId(),
            'delivery_id'    => $deliveryId,
            'region'         => $rule['region'],
            'first'          => $rule['first'],
            'first_fee'      => $rule['first_fee'],
            'additional'     => $rule['additional'],
            'additional_fee' => $rule['additional_fee'],
            'min_price'      => $rule['min_price'],
            'min_num'        => $rule['min_num'],
            'delivery_type'  => $rule['delivery_type'],
            'app_id'         => 0,
            'created_at'     => $now,
        ]);
    }
    out("  [配送] 模板「{$d['name']}」+ " . count($d['rules']) . ' 条计费规则');
}
out('  [配送] 完成：' . table('yxshop_deliveries')->count() . ' 个模板 / '
    . table('yxshop_delivery_rules')->count() . ' 条规则');

/* ============================================================
 * 4. 商品类型 + 商品属性
 * ============================================================ */
$typeList = [
    ['name' => '手机数码', 'desc' => '手机、平板、电脑及配件', 'icon' => '📱'],
    ['name' => '家用电器', 'desc' => '冰箱、洗衣机、空调等大件家电', 'icon' => '🔌'],
    ['name' => '服饰鞋包', 'desc' => '男女装、鞋靴、箱包配饰', 'icon' => '👕'],
    ['name' => '美妆个护', 'desc' => '护肤、彩妆、洗护用品', 'icon' => '💄'],
    ['name' => '食品生鲜', 'desc' => '零食、粮油、水果蔬菜', 'icon' => '🍎'],
    ['name' => '母婴玩具', 'desc' => '奶粉、纸尿裤、益智玩具', 'icon' => '🧸'],
    ['name' => '家居家装', 'desc' => '家具、灯饰、床上用品', 'icon' => '🛋️'],
    ['name' => '图书文娱', 'desc' => '图书、音像、乐器文具', 'icon' => '📚'],
];

$typeIds = [];
foreach ($typeList as $t) {
    $typeId = table('yxshop_item_types')->where('name', $t['name'])->value('id');
    if (!$typeId) {
        $emptyId = table('yxshop_item_types')
            ->where(function ($q) {
                $q->whereNull('name')->orWhere('name', '');
            })->value('id');
        $typeId = $emptyId ?: newId();
        $payload = [
            'name'       => $t['name'],
            'type_desc'  => $t['desc'],
            'type_icon'  => $t['icon'],
            'app_id'     => 0,
            'status'     => 1,
            'sort'       => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if ($emptyId) {
            table('yxshop_item_types')->where('id', $emptyId)->update($payload);
        } else {
            $payload['id'] = $typeId;
            table('yxshop_item_types')->insert($payload);
        }
    }
    $typeIds[$t['name']] = (string) $typeId;
}

$attributeList = [
    ['type' => '手机数码', 'name' => '颜色',   'values' => ['黑色', '白色', '星光色', '远峰蓝']],
    ['type' => '手机数码', 'name' => '存储',   'values' => ['128G', '256G', '512G', '1T']],
    ['type' => '家用电器', 'name' => '能效等级', 'values' => ['一级', '二级', '三级']],
    ['type' => '家用电器', 'name' => '容量',   'values' => ['单人', '双人', '三人']],
    ['type' => '服饰鞋包', 'name' => '尺码',   'values' => ['S', 'M', 'L', 'XL', 'XXL']],
    ['type' => '服饰鞋包', 'name' => '颜色',   'values' => ['黑色', '米白', '藏青', '焦糖']],
    ['type' => '美妆个护', 'name' => '肤质',   'values' => ['干性', '油性', '混合性']],
    ['type' => '食品生鲜', 'name' => '规格',   'values' => ['袋装', '盒装', '礼盒装']],
    ['type' => '母婴玩具', 'name' => '适用年龄', 'values' => ['0-1岁', '1-3岁', '3-6岁', '6岁以上']],
    ['type' => '家居家装', 'name' => '材质',   'values' => ['实木', '布艺', '皮质', '金属']],
];

foreach ($attributeList as $a) {
    $typeId = $typeIds[$a['type']] ?? null;
    if (!$typeId) {
        continue;
    }
    $exists = table('yxshop_item_attributes')
        ->where('type_id', $typeId)
        ->where('name', $a['name'])
        ->value('id');
    if ($exists) {
        // yxshop_item_attributes 没有 status 列，只有 values / sort 可更新
        table('yxshop_item_attributes')->where('id', $exists)->update([
            'values' => json_encode($a['values'], JSON_UNESCAPED_UNICODE),
        ]);
        continue;
    }
    table('yxshop_item_attributes')->insert([
        'id'         => newId(),
        'name'       => $a['name'],
        'type_id'    => $typeId,
        'values'     => json_encode($a['values'], JSON_UNESCAPED_UNICODE),
        'sort'       => 0,
        'app_id'     => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}
out('  [类型/属性] 完成：' . table('yxshop_item_types')->count() . ' 个类型 / '
    . table('yxshop_item_attributes')->count() . ' 个属性');

/* ============================================================
 * 5. 促销活动
 * ============================================================ */
$promotionList = [
    ['title' => '限时秒杀 · 爆款直降', 'type' => 1, 'rule' => ['discount' => 0.8, 'limit' => 50],
     'start' => $now - 86400, 'end' => $now + 86400 * 6],
    ['title' => '新客首单立减 20 元', 'type' => 2, 'rule' => ['reduce' => 20, 'limit' => 200],
     'start' => $now - 86400, 'end' => $now + 86400 * 15],
    ['title' => '满 199 减 30', 'type' => 3, 'rule' => ['threshold' => 199, 'reduce' => 30],
     'start' => $now - 86400, 'end' => $now + 86400 * 10],
    ['title' => '拼团 3 人成团立减 15', 'type' => 4, 'rule' => ['group_size' => 3, 'reduce' => 15],
     'start' => $now - 86400, 'end' => $now + 86400 * 7],
    ['title' => '节日特惠 · 全场 9 折', 'type' => 5, 'rule' => ['discount' => 0.9],
     'start' => $now - 86400, 'end' => $now + 86400 * 20],
];

foreach ($promotionList as $p) {
    $exists = table('yxshop_promotions')->where('title', $p['title'])->value('id');
    $payload = [
        'title'      => $p['title'],
        'type'       => $p['type'],
        'rule'       => json_encode($p['rule'], JSON_UNESCAPED_UNICODE),
        'start_time' => $p['start'],
        'end_time'   => $p['end'],
        'status'     => 1,
        'app_id'     => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ];
    if ($exists) {
        table('yxshop_promotions')->where('id', $exists)->update($payload);
        continue;
    }
    $emptyId = table('yxshop_promotions')
        ->where(function ($q) {
            $q->whereNull('title')->orWhere('title', '');
        })->value('id');
    if ($emptyId) {
        table('yxshop_promotions')->where('id', $emptyId)->update($payload);
    } else {
        $payload['id'] = newId();
        table('yxshop_promotions')->insert($payload);
    }
}
out('  [促销] 完成：' . table('yxshop_promotions')->where('status', 1)->count() . ' 个进行中');

/* ============================================================
 * 5.1 促销商品（prom_items）
 * 把促销活动与商品关联起来，否则活动详情页没有任何商品可展示。
 * ============================================================ */
$promotionIds = table('yxshop_promotions')
    ->where('status', 1)
    ->whereNotNull('title')
    ->where('title', '<>', '')
    ->pluck('id', 'title')
    ->toArray();

$itemRows = table('yxshop_items')
    ->where('app_id', 0)
    ->where('status', 1)
    ->get(['id', 'name', 'price', 'stock']);

// 折扣系数：不同活动类型给不同力度
$discountMap = [
    '限时秒杀 · 爆款直降' => 0.7,
    '新客首单立减 20 元' => 0.9,
    '满 199 减 30'        => 0.85,
    '拼团 3 人成团立减 15' => 0.8,
    '节日特惠 · 全场 9 折' => 0.9,
];

$promItemCount = 0;
foreach ($promotionIds as $title => $promotionId) {
    $rate = $discountMap[$title] ?? 0.9;
    // 每个活动挂 3 个商品
    foreach (array_slice($itemRows->all(), 0, 3) as $index => $item) {
        $exists = table('yxshop_prom_items')
            ->where('prom_id', $promotionId)
            ->where('item_id', $item->id)
            ->value('id');
        $payload = [
            'prom_id'     => $promotionId,
            'item_id'     => $item->id,
            'prom_price'  => round((float) $item->price * $rate, 2),
            'prom_stock'  => max(1, (int) floor((int) $item->stock * 0.3)),
            'prom_sales'  => 0,
            'prom_status' => 1,
            'sort'        => $index,
            'name'        => $item->name,
            'type'        => 1,
            'expression'  => (string) $item->price,
            'app_id'      => 0,
            'created_at'  => $now,
            'updated_at'  => $now,
        ];
        if ($exists) {
            table('yxshop_prom_items')->where('id', $exists)->update($payload);
        } else {
            $payload['id'] = newId();
            table('yxshop_prom_items')->insert($payload);
            $promItemCount++;
        }
    }
}
out("  [促销商品] 新增 {$promItemCount} 条，共 "
    . table('yxshop_prom_items')->where('prom_status', 1)->count() . ' 条启用');

/* ============================================================
 * 5.2 帮助中心（文章分类 + 文章）
 * 对应管理端「内容管理 → 文章管理 / 文章分类」，小程序端为「帮助中心」。
 * ============================================================ */
$categoryList = [
    ['name' => '新手入门', 'sort' => 1],
    ['name' => '配送与物流', 'sort' => 2],
    ['name' => '支付与优惠', 'sort' => 3],
    ['name' => '售后与退换', 'sort' => 4],
    ['name' => '账号与安全', 'sort' => 5],
    ['name' => '常见问题', 'sort' => 6],
];

$categoryIds = [];
foreach ($categoryList as $c) {
    $payload = [
        'name'       => $c['name'],
        'parent_id'  => 0,
        'app_id'     => 0,
        'status'     => 1,
        'sort'       => $c['sort'],
        'deleted_at' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ];
    $categoryId = table('yxshop_article_categories')->where('name', $c['name'])->value('id');
    if ($categoryId) {
        table('yxshop_article_categories')->where('id', $categoryId)->update($payload);
    } else {
        // 复用一条空名称的分类行，避免产生冗余记录
        $emptyId = table('yxshop_article_categories')
            ->where(function ($q) {
                $q->whereNull('name')->orWhere('name', '');
            })->value('id');
        if ($emptyId) {
            table('yxshop_article_categories')->where('id', $emptyId)->update($payload);
            $categoryId = $emptyId;
        } else {
            $categoryId = newId();
            $payload['id'] = $categoryId;
            table('yxshop_article_categories')->insert($payload);
        }
    }
    $categoryIds[$c['name']] = (string) $categoryId;
}

// 文章内容（帮助中心正文，Markdown 纯文本）
$articleList = [
    ['title' => '如何注册账号', 'category' => '新手入门', 'sort' => 1, 'is_top' => 1,
     'summary' => '一分钟完成注册，开启购物之旅',
     'content' => "欢迎使用盈锐商贸！\n\n一、注册步骤\n1. 打开小程序，点击「我的」页面右上角的登录按钮。\n2. 选择微信授权登录，或使用手机号 + 验证码注册。\n3. 完善昵称与收货地址，即可开始购物。\n\n二、注意事项\n- 首次登录建议先绑定手机号，便于接收订单通知。\n- 未登录状态下也可以浏览商品，但无法加入购物车。\n\n如果遇到问题，可在「帮助中心 → 常见问题」中查找答案，或联系在线客服。"],
    ['title' => '收货地址如何填写', 'category' => '新手入门', 'sort' => 2, 'is_top' => 0,
     'summary' => '地址填写的完整步骤与省市区选择技巧',
     'content' => "一、添加地址\n进入「我的 → 收货地址」，点击右上角「新增」。\n\n二、填写要点\n- 收货人：请填写真实姓名\n- 手机号：用于快递派送通知\n- 省市区：点击后逐级选择，系统会自动带出已选地区名称\n- 详细地址：填写街道、门牌号、楼层、房间号\n\n三、默认地址\n列表中标记为「默认」的地址会在下单时自动选中，您可以在列表中随时切换。"],
    ['title' => '配送方式与运费说明', 'category' => '配送与物流', 'sort' => 1, 'is_top' => 1,
     'summary' => '包邮门槛、续件运费与发货时效',
     'content' => "一、运费计算\n下单时系统会根据您选择的配送方式、所选地区、商品数量与金额自动计算运费。\n\n- 全国包邮：默认模板，不收取运费\n- 标准快递：首件运费 10 元，续件每件加收 3 元，满 99 元包邮\n- 华东次日达：首件 8 元，续件每件加收 2 元，满 199 元包邮\n- 大件物流：按件计费，满 5 件包邮\n\n二、发货时效\n- 现货商品：付款后 48 小时内发货\n- 预售商品：以商品详情页标注的预计发货时间为准\n\n三、偏远地区\n部分偏远地区的配送方式与时效会有所不同，具体以下单页展示为准。"],
    ['title' => '什么时候可以发货？', 'category' => '配送与物流', 'sort' => 2, 'is_top' => 0,
     'summary' => '发货流程与物流轨迹查看方式',
     'content' => "一、发货流程\n订单支付成功后进入「待发货」状态，商家会在 48 小时内完成备货与出库。\n\n二、查看物流\n1. 进入「我的 → 我的订单」，找到对应订单。\n2. 点击订单进入详情，点击「查看物流」即可查看快递公司与运单号。\n3. 物流轨迹由快递公司同步，可能存在几分钟的延迟。\n\n三、修改地址\n订单在「待发货」状态下，可在订单详情页联系修改收件信息。"],
    ['title' => '支持哪些支付方式？', 'category' => '支付与优惠', 'sort' => 1, 'is_top' => 0,
     'summary' => '微信支付、支付宝与余额支付说明',
     'content' => "一、支付方式\n- 微信支付：推荐使用，下单后直接调起微信支付流程\n- 支付宝：部分场景下支持\n- 余额支付：可使用账户余额抵扣，余额不足时可组合支付\n\n二、支付失败怎么办？\n- 检查网络是否正常\n- 确认支付账户余额或绑定卡是否充足\n- 若已扣款但订单状态未更新，请稍等几分钟刷新，或联系客服并提供支付流水号\n\n三、支付安全\n商城使用加密传输，支付过程不会保存您的支付密码。"],
    ['title' => '优惠券如何使用？', 'category' => '支付与优惠', 'sort' => 2, 'is_top' => 0,
     'summary' => '优惠券领取、叠加规则与失效说明',
     'content' => "一、如何领取\n进入「我的 → 优惠券」，可领取平台发放的优惠券；活动期间下单也会自动发放。\n\n二、使用规则\n- 结算页会自动选择可用优惠券中优惠力度最大的一张\n- 部分优惠券仅限指定商品或分类使用\n- 优惠券不可叠加使用，每单限用一张\n\n三、失效说明\n优惠券有有效期，过期后自动失效且不退还已领取的额度。已过期的优惠券可以在列表中查看。"],
    ['title' => '退货退款流程', 'category' => '售后与退换', 'sort' => 1, 'is_top' => 0,
     'summary' => '7 天无理由退货与退款到账时间',
     'content' => "一、申请售后\n1. 进入「我的 → 我的订单」，找到已完成的订单。\n2. 点击「申请售后」，选择退款类型并填写原因。\n3. 提交后进入审核，审核通过后按提示寄回商品。\n\n二、退款时效\n- 商家同意后 1-3 个工作日内发起退款\n- 微信/支付宝原路退回，到账时间以支付渠道为准\n\n三、7 天无理由\n商品签收后 7 天内、且不影响二次销售的情况下支持无理由退货。定制类商品除外。"],
    ['title' => '如何修改账号密码？', 'category' => '账号与安全', 'sort' => 1, 'is_top' => 0,
     'summary' => '修改密码与绑定手机号的操作路径',
     'content' => "一、修改密码\n1. 进入「我的 → 设置 → 账号与安全」。\n2. 选择「修改密码」，输入原密码与新密码。\n3. 保存后重新登录即可生效。\n\n二、忘记密码\n若忘记原密码，可通过绑定手机号接收验证码重置。\n\n三、账号安全建议\n- 定期更换密码，避免与其他网站复用\n- 不在公共设备上保存登录状态\n- 涉及资金的操作请仔细核对订单信息"],
];

$articleCount = 0;
foreach ($articleList as $a) {
    $categoryId = $categoryIds[$a['category']] ?? null;
    if (!$categoryId) {
        continue;
    }
    $exists = table('yxshop_articles')->where('title', $a['title'])->value('id');
    $payload = [
        'title'       => $a['title'],
        'category_id' => $categoryId,
        'author'      => '盈锐商贸',
        'excerpt'     => $a['summary'],
        'subtitle'    => $a['summary'],
        'description' => $a['summary'],
        'content'     => $a['content'],
        'sort'        => $a['sort'],
        'is_top'      => $a['is_top'],
        'status'      => 1,
        'view_count'  => 0,
        'views'       => 0,
        'likes'       => 0,
        'version'     => 1,
        'app_id'      => 0,
        'deleted_at'  => 0,
        'created_at'  => $now,
        'updated_at'  => $now,
        'published_at' => $now,
    ];
    if ($exists) {
        table('yxshop_articles')->where('id', $exists)->update($payload);
        continue;
    }
    $payload['id'] = newId();
    table('yxshop_articles')->insert($payload);
    $articleCount++;
}
out("  [帮助中心] 新增 {$articleCount} 篇文章，共 "
    . table('yxshop_articles')->where('status', 1)->where('deleted_at', 0)->count() . ' 篇已发布');

/* ============================================================
 * 6. 广告位
 * ============================================================ */
$adList = [
    ['title' => '首页顶部横幅', 'position' => 'home_banner', 'image' => '/uploads/ad/banner-1.png',
     'link' => '/pages/promotion/list/list', 'sort' => 1, 'content' => '首页轮播广告位 1'],
    ['title' => '首页中部横幅', 'position' => 'home_middle', 'image' => '/uploads/ad/banner-2.png',
     'link' => '/pages/promotion/list/list?type=1', 'sort' => 2, 'content' => '首页轮播广告位 2'],
    ['title' => '分类页横幅', 'position' => 'category_banner', 'image' => '/uploads/ad/banner-3.png',
     'link' => '/pages/item-list/item-list', 'sort' => 3, 'content' => '分类页顶部广告'],
    ['title' => '搜索页横幅', 'position' => 'search_banner', 'image' => '/uploads/ad/banner-4.png',
     'link' => '/pages/item-list/item-list', 'sort' => 4, 'content' => '搜索页顶部广告'],
];

foreach ($adList as $a) {
    $exists = table('yxshop_advertisements')->where('title', $a['title'])->value('id');
    $payload = [
        'title'      => $a['title'],
        'position'   => $a['position'],
        'image'      => $a['image'],
        'link'       => $a['link'],
        'sort'       => $a['sort'],
        'content'    => $a['content'],
        'count'      => json_encode(['click' => 0, 'view' => 0], JSON_UNESCAPED_UNICODE),
        'status'     => 1,
        'is_show'    => 1,
        'app_id'     => 0,
        'start_time' => $now - 86400,
        'end_time'   => $now + 86400 * 30,
        'created_at' => $now,
        'updated_at' => $now,
    ];
    if ($exists) {
        table('yxshop_advertisements')->where('id', $exists)->update($payload);
        continue;
    }
    $payload['id'] = newId();
    table('yxshop_advertisements')->insert($payload);
}
out('  [广告] 完成：' . table('yxshop_advertisements')->count() . ' 个广告位');

/* ============================================================
 * 7. 商品关联配送模板 + 标签
 * ============================================================ */
$defaultDeliveryId = table('yxshop_deliveries')->where('name', '标准快递')->value('id');
if ($defaultDeliveryId) {
    table('yxshop_items')
        ->where('app_id', 0)
        ->where(function ($q) {
            $q->whereNull('shipping_template_id')->orWhere('shipping_template_id', 0);
        })
        ->update(['shipping_template_id' => $defaultDeliveryId]);
    out('  [商品] 已绑定默认配送模板');
}

out('');
out('基础数据填充完成。');
