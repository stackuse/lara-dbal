# Libra\Dbal 扩展

DBAL: Database Abstraction Layer 数据库抽象层

对 laravel database对扩展，使它更加适配mongodb

## 优化

- 不允许跨库连表查询

## 改动

- _id 转换成 id，和 MySQL 保持一致
- 对many to many, 和 morph many 进行改造，支持不连表查询，同时也支持mongodb

## 参考

- [Laravel MongoDB](https://github.com/jenssegers/laravel-mongodb)
