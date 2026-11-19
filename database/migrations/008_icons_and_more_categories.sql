-- 新增分类（与 Icons 注册表对应，icon 存 key=slug）
INSERT IGNORE INTO categories (name, slug, description, icon, position, is_builtin) VALUES
('前端',     'frontend',      '前端开发项目',            'frontend',      1, 1),
('后端',     'backend',       '后端开发项目',            'backend',       2, 1),
('AI',       'ai',            '人工智能 / 大模型相关',   'ai',            3, 1),
('Web',      'web',           '网页 / Web 开发',         'web',           4, 1),
('手机开发', 'mobile',        '移动端 / App 开发',       'mobile',        5, 1),
('游戏',     'game',          '游戏开发 / 游戏项目',     'game',         13, 1),
('娱乐',     'entertainment', '影音 / 娱乐项目',         'entertainment',14, 1),
('插件',     'plugin',        '插件 / 扩展 / 模块',      'plugin',       15, 1),
('工业软件', 'industrial',    '工业 / 自动化软件',       'industrial',   16, 1);

-- 把历史 emoji 图标统一替换成 SVG key（key 与 slug 一致）
UPDATE categories SET icon = slug WHERE icon = '' OR icon NOT IN (
    'all','frontend','backend','ai','web','mobile','cms','forum','blog',
    'image-host','cloud-disk','download','nav','game','entertainment',
    'plugin','industrial','other'
);

-- 重排全部内置分类的展示顺序
UPDATE categories SET position = CASE slug
    WHEN 'frontend' THEN 1
    WHEN 'backend' THEN 2
    WHEN 'ai' THEN 3
    WHEN 'web' THEN 4
    WHEN 'mobile' THEN 5
    WHEN 'cms' THEN 6
    WHEN 'forum' THEN 7
    WHEN 'blog' THEN 8
    WHEN 'image-host' THEN 9
    WHEN 'cloud-disk' THEN 10
    WHEN 'download' THEN 11
    WHEN 'nav' THEN 12
    WHEN 'game' THEN 13
    WHEN 'entertainment' THEN 14
    WHEN 'plugin' THEN 15
    WHEN 'industrial' THEN 16
    WHEN 'other' THEN 99
    ELSE position
END WHERE is_builtin = 1;
