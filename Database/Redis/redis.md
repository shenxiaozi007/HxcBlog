# 我的redis整理
<!-- GFM-TOC -->
* [一、概述](#一概述)
* [二、数据类型](#二数据类型)
    * [STRING](#string)
    * [LIST](#list)
    * [SET](#set)
    * [HASH](#hash)
    * [ZSET](#zset)
* [三、数据结构](#三数据结构)
    * [字典](#字典)
    * [跳跃表](#跳跃表)
* [四、使用场景](#四使用场景)
    * [计数器](#计数器)
    * [缓存](#缓存)
    * [查找表](#查找表)
    * [消息队列](#消息队列)
    * [会话缓存](#会话缓存)
    * [分布式锁实现](#分布式锁实现)
    * [其它](#其它)
* [五、Redis 与 Memcached](#五redis-与-memcached)
    * [数据类型](#数据类型)
    * [数据持久化](#数据持久化)
    * [分布式](#分布式)
    * [内存管理机制](#内存管理机制)
* [六、键的过期时间](#六键的过期时间)
* [七、数据淘汰策略](#七数据淘汰策略)
* [八、持久化](#八持久化)
    * [RDB 持久化](#rdb-持久化)
    * [AOF 持久化](#aof-持久化)
* [九、事务](#九事务)
* [十、事件](#十事件)
    * [文件事件](#文件事件)
    * [时间事件](#时间事件)
    * [事件的调度与执行](#事件的调度与执行)
* [十一、复制](#十一复制)
    * [连接过程](#连接过程)
    * [主从链](#主从链)
* [十二、Sentinel](#十二sentinel)
* [十三、分片](#十三分片)
* [十四、一个简单的论坛系统分析](#十四一个简单的论坛系统分析)
    * [文章信息](#文章信息)
    * [点赞功能](#点赞功能)
    * [对文章进行排序](#对文章进行排序)
* [参考资料](#参考资料)
<!-- GFM-TOC -->
## 一. 概述

Redis 是速度非常快的非关系型（NoSQL）内存键值数据库，可以存储键和五种不同类型的值之间的映射。

`键的类型`只能为`字符串`，`值`支持`五种数据类型`：`字符串`、`列表`、`集合`、`散列表`、`有序集合`。

Redis 支持很多特性，例如将内存中的数据持久化到硬盘中，使用复制来扩展读性能，使用分片来扩展写性能。

### 1.1 redis单线程问题

所谓的单线程指的是`网络请求模块`使用了一个线程（所以不需考虑并发安全性），即一个线程处理所有网络请求，其他模块仍用了多个线程。

redis采用多路复用机制：即多个网络socket复用一个io线程，实际是单个线程通过记录跟踪每一个Sock(I/O流)的状态来同时管理多个I/O流. 

### 1.2 Redis特点

- Redis支持数据的`持久化`，可以将内存中的数据保存在磁盘中，重启的时候可以再次加载进行使用。

- Redis不仅仅支持简单的key-value类型的数据，同时还提供`String，list，set，zset，hash`等数据结构的存储。

- Redis支持数据的`备份`，即master-slave模式的数据备份。

- `性能极高` – Redis能读的速度是110000次/s,写的速度是81000次/s 。

- 原子 – Redis的所有操作都是`原子性`的，同时Redis还支持对几个操作全并后的原子性执行。

- 丰富的特性 – Redis还支持 `publish/subscribe`, 通知, 设置key有效期等等特性。

## 二. 数据类型

| 数据类型 | 可以存储的值 | 操作 |
| --- | --- | --- |
| STRING | 字符串、整数或者浮点数 | 对整个字符串或者字符串的其中一部分执行操作</br> 对整数和浮点数执行自增或者自减操作 |
| LIST | 列表 | 从两端压入或者弹出元素 </br> 对单个或者多个元素进行修剪，</br> 只保留一个范围内的元素 |
| SET | 无序集合 | 添加、获取、移除单个元素</br> 检查一个元素是否存在于集合中</br> 计算交集、并集、差集</br> 从集合里面随机获取元素 |
| HASH | 包含键值对的无序散列表 | 添加、获取、移除单个键值对</br> 获取所有键值对</br> 检查某个键是否存在|
| ZSET | 有序集合 | 添加、获取、删除元素</br> 根据分值范围或者成员来获取元素</br> 计算一个键的排名 |


### 2.1 STRING(字符串)

![图片](https://shenxiaozi007.github.io/HxcBlog/images/imagesRedis/redis1.png) 

常用命令

```
1.set key value：设定key持有指定的字符串value，如果该key存在则进行覆盖操作,总是返回OK

2.get key: 获取key的value。如果与该key关联的value不是String类型，redis将返回错误信息，因为get命令只能用于获取String value；如果该key不存在，返回null。

3.getset key value：先获取该key的值，然后在设置该key的值。

4.incr key：将指定的key的value原子性的递增1. 如果该key不存在，其初始值为0，在incr之后其值为1。如果value的值不能转成整型，如hello，该操作将执行失败并返回相应的错误信息

5.decr key：将指定的key的value原子性的递减1.如果该key不存在，其初始值为0，在incr之后其值为-1。如果value的值不能转成整型，如hello，该操作将执    行失败并返回相应的错误信息。

6.incrby key increment：将指定的key的value原子性增加increment，如果该key不存在，器初始值为0，在incrby之后，该值为increment。如果该值不能转成    整型，如hello则失败并返回错误信息

7.decrby key decrement：将指定的key的value原子性减少decrement，如果该key不存在，器初始值为0，在decrby之后，该值为decrement。如果该值不能    转成整型，如hello则失败并返回错误信息

8.append key value：如果该key存在，则在原有的value后追加该值；如果该key    不存在，则重新创建一个key/value
```
```
> set hello world
OK
> get hello
"world"
> del hello
(integer) 1
> get hello
(nil)
```

### 2.2 LIST(列表)

Redis列表是简单的字符串列表，按照插入顺序排序。你可以添加一个元素到列表的头部（左边）或者尾部（右边）

一个列表最多可以包含 232 - 1 个元素 (4294967295, 每个列表超过40亿个元素)。 

>- List数据结构是`双端链表结构`，是双向的，可以在`链表左，右两边`分别进行`插入和删除`数据；
>- 也可以把list看成一种队列，所以在很多时候可以用redis用作消息队列，这个时候它的作用就类似activeMq啦；
>- 应用案例有时间轴数据，评论列表，消息传递等等，它可以提供简便的分页，读写操作。

![图片](https://shenxiaozi007.github.io/HxcBlog/images/imagesRedis/redis2.png) 

常用命令

```
1.lpush key value1 value2...：在指定的key所关联的list的头部插入所有的values，如果该key不存在，该命令在插入的之前创建一个与该key关联的空链表，之后再向该链表的头部插入数据。插入成功，返回元素的个数。

2.rpush key value1、value2…：在该list的尾部添加元素

3.lrange key start end：获取链表中从start到end的元素的值，start、end可为负数，若为-1则表示链表尾部的元素，-2则表示倒数第二个，依次类推… 

4.lpushx key value：仅当参数中指定的key存在时（如果与key管理的list中没有值时，则该key是不存在的）在指定的key所关联的list的头部插入value。

5.rpushx key value：在该list的尾部添加元素

6.lpop key：返回并弹出指定的key关联的链表中的第一个元素，即头部元素

7.rpop key：从尾部弹出元素

8.rpoplpush resource destination：将链表中的尾部元素弹出并添加到头部

9.llen key：返回指定的key关联的链表中的元素的数量。

10.lset key index value：设置链表中的index的脚标的元素值，0代表链表的头元素，-1代表链表的尾元素。
```

```
> rpush list-key item
(integer) 1
> rpush list-key item2
(integer) 2
> rpush list-key item
(integer) 3

> lrange list-key 0 -1
1) "item"
2) "item2"
3) "item"

> lindex list-key 1
"item2"

> lpop list-key
"item"

> lrange list-key 0 -1
1) "item2"
2) "item"
```

### 2.3 SET(无序集合)


Redis 的 Set 是 String 类型的无序集合。集合成员是唯一的，这就意味着集合中不能出现重复的数据。

Redis 中集合是通过哈希表实现的，所以添加，删除，查找的复杂度都是 O(1)。

集合中最大的成员数为 232 - 1 (4294967295, 每个集合可存储40多亿个成员)。 

>- Set 就是一个集合，集合的概念就是一堆不重复值的组合。利用 Redis 提供的 Set 数据结构，可以存储一些集合性的数据。
>- 比如在微博应用中，可以将一个用户所有的关注人存在一个集合中，将其所有粉丝存在一个集合。
>- 因为 Redis 非常人性化的为集合提供了求交集、并集、差集等操作，那么就可以非常方便的实现如共同关注、共同喜好、二度好友等功能，对上面的所有集合操作，你还可以使用不同的命令选择将结果返回给客户端还是存集到一个新的集合中。
  
  1.共同好友、二度好友
  2.利用唯一性，可以统计访问网站的所有独立 IP
  3.好友推荐的时候，根据 tag 求交集，大于某个 threshold 就可以推荐
![图片](https://shenxiaozi007.github.io/HxcBlog/images/imagesRedis/redis3.png) 


常用命令

```
添加或删除元素
1.sadd key values[value1、value2……]:向set中添加数据，如果该key的值有则不会重复添加
例如:sadd myset  a b c

2.srem key members[member1、menber2…]:删除set中的指定成员
例如:srem myset 1 2 3

获得集合中的元素
1.smembers key :获取set中所有的成员
smembers myset

2.sismember key menber :判断参数中指定的成员是否在该set中，1表示存在，0表示不存在或者该key本身就不存在(无论集合中有多少元素都可以极速的返回结果)

集合的差集运算 A-B
sdiff key1 key2 … : 返回key1与key2中相差的成员，而且与key的顺序有关。即返回差集。

集合的交集运算 
sinter key1 key2 key3… :返回交集

集合的并集运算 
sunion key1 key2 key3… : 返回并集
```

```
> sadd set-key item
(integer) 1
> sadd set-key item2
(integer) 1
> sadd set-key item3
(integer) 1
> sadd set-key item
(integer) 0

> smembers set-key
1) "item"
2) "item2"
3) "item3"

> sismember set-key item4
(integer) 0
> sismember set-key item
(integer) 1

> srem set-key item2
(integer) 1
> srem set-key item2
(integer) 0

> smembers set-key
1) "item"
2) "item3"
```
### 2.4 ZSET(有序集合)

 Redis 有序集合和集合一样也是string类型元素的集合,且不允许重复的成员。

不同的是每个元素都会关联一个double类型的分数。redis正是通过分数来为集合中的成员进行从小到大的排序。

有序集合的成员是唯一的,但分数(score)却可以重复。

集合是通过哈希表实现的，所以添加，删除，查找的复杂度都是O(1)。 集合中最大的成员数为 232 - 1 (4294967295, 每个集合可存储40多亿个成员)。 

>- zset是set的一个升级版本，他在set的基础上增加了一个顺序属性，这一属性在添加修改元素的时候可以指定，每次指定后，zset会自动重新按新的值调整顺序。 可以对指定键的值进行排序权重的设定，它应用排名模块比较多。

>- 比如一个存储全班同学成绩的 Sorted Sets，其集合 value 可以是同学的学号，而 score 就可以是其考试得分，这样在数据插入集合的时候，就已经进行了天然的排序。另外还可以用 Sorted Sets 来做带权重的队列，比如普通消息的 score 为1，重要消息的 score 为2，然后工作线程可以选择按 score 的倒序来获取工作任务，让重要的任务优先执行。
 
 zset集合可以完成有序执行、按照优先级执行的情况；

![图片](https://shenxiaozi007.github.io/HxcBlog/images/imagesRedis/redis5.png) 

常用命令

```
1.添加元素
zadd key score member score2 member2…:将所有成员以及该成员的分数存放到sorted-set中。如果该元素已经存在则会用新的分数替换原有的分数。返回值是新加入到集合中的元素个数。(根据分数升序排列)

2.获得元素
zscore key member ：返回指定成员的分数
zcard key ：获得集合中的成员数量

3.删除元素
zrem key member[member…] ：移除集合中指定的成员，可以指定多个成员

4.范围查询
zrange key strat end [withscores]：获取集合中角标为start-end的成员，[withscore]参数表明返回的成员包含其分数。

zremrangebyrank key start stop ：按照排名范围删除元素

zremrangescore key  min max ：按照分数范围删除元素
```

例子
```
> zadd zset-key 728 member1
(integer) 1
> zadd zset-key 982 member0
(integer) 1
> zadd zset-key 982 member0
(integer) 0

> zrange zset-key 0 -1 withscores
1) "member1"
2) "728"
3) "member0"
4) "982"

> zrangebyscore zset-key 0 800 withscores
1) "member1"
2) "728"

> zrem zset-key member1
(integer) 1
> zrem zset-key member1
(integer) 0

> zrange zset-key 0 -1 withscores
1) "member0"
2) "982"
```

### 2.5 HASH(哈希)

Redis hash 是一个 string 类型的 field 和 value 的映射表，hash 特别适合用于存储对象。

Redis 中每个 hash 可以存储 232 - 1 键值对（40多亿）

![图片](https://shenxiaozi007.github.io/HxcBlog/images/imagesRedis/redis4.png) 

常用命令

```
1.赋值
hset key  field value : 为指定的key设定field/value对

hmset key field1 value1 field2 value2  field3 value3     为指定的key设定多个field/value对

2.取值
hget key field : 返回指定的key中的field的值

hmget key field1 field2 field3 : 获取key中的多个field值

hkeys key : 获取所有的key

hvals key :获取所有的value

hgetall key : 获取key中的所有field 中的所有field-value

3.删除
hdel key field[field…] : 可以删除一个或多个字段，返回是被删除的字段个数

del key : 删除整个list

4.增加数字
hincrby key field increment ：设置key中field的值增加increment，如: age增加20
hincrby myhash age 5

```

```
> hset hash-key sub-key1 value1
(integer) 1
> hset hash-key sub-key2 value2
(integer) 1
> hset hash-key sub-key1 value1
(integer) 0

> hgetall hash-key
1) "sub-key1"
2) "value1"
3) "sub-key2"
4) "value2"

> hdel hash-key sub-key2
(integer) 1
> hdel hash-key sub-key2
(integer) 0

> hget hash-key sub-key1
"value1"

> hgetall hash-key
1) "sub-key1"
2) "value1"
```


### 2.6 key的通用操作  

常用命令

```
keys pattern : 获取所有与pattern匹配的key ，返回所有与该key匹配的keys。 *表示任意一个或者多个字符， ?表示任意一个字符

del key1 key2… ：删除指定的key 

exists key ：判断该key是否存在，1代表存在，0代表不存在

rename key newkey ：为key重命名

expire key second：设置过期时间，单位秒

ttl key：获取该key所剩的超时时间，如果没有设置超时，返回-1，如果返回-2表示超时不存在。

persist key:持久化key   

type key：获取指定key的类型。该命令将以字符串的格式返回。返回的字符串为string 、list 、set 、hash 和 zset，如果key不存在返回none。
```

```
192.168.25.153:6379> expire Hello 100
(integer) 1
192.168.25.153:6379> ttl Hello
(integer) 77

192.168.25.153:6379> type newcompany
none
```
## 三 数据结构

### 1 字典

dictht 是一个散列表结构，使用拉链法解决哈希冲突。

### 2 跳跃表

是有序集合的底层实现之一。

## 四 使用场景

   - 计数器(String)
   
   可以对 String 进行自增自减运算，从而实现计数器功能。
   Redis 这种内存型数据库的读写性能非常高，很适合存储频繁读写的计数量。
   
   - 缓存
   
   将热点数据放到内存中，设置内存的最大使用量以及淘汰策略来保证缓存的命中率。
   
   - 查找表
   
   例如 DNS 记录就很适合使用 Redis 进行存储。
   
   查找表和缓存类似，也是利用了 Redis 快速的查找特性。但是查找表的内容不能失效，而缓存的内容可以失效，因为缓存不作为可靠的数据来源。
   
   - 消息队列(list)
   
   List 是一个双向链表，可以通过 lpush 和 rpop 写入和读取消息
   
   不过最好使用 Kafka、RabbitMQ 等消息中间件。
   
   - 会话缓存
   
   可以使用 Redis 来统一存储多台应用服务器的会话信息。
   
   当应用服务器不再存储用户的会话信息，也就不再具有状态，一个用户可以请求任意一个应用服务器，从而更容易实现高可用性以及可伸缩性。
   
   - 分布式锁实现
   
   在分布式场景下，无法使用单机环境下的锁来对多个节点上的进程进行同步。
   
   可以使用 Redis 自带的 SETNX 命令实现分布式锁，除此之外，还可以使用官方提供的 RedLock 分布式锁实现。
   
   - 其它
   
   1. Set 可以实现`交集`、`并集`等操作，从而实现共同好友等功能。
   
   2. ZSet 可以实现有序性操作，从而实现排行榜等功能。ZSet多了一个权重参数score,集合中的元素能够按score进行排列。可以做`排行榜应用`，取`TOP N操作`。另外，参照另一篇《分布式之延时任务方案解析》，该文指出了sorted set可以用来做`延时任务`。最后一个应用就是可以做`范围查找`


## 五 redis的过期策略以及内存淘汰机制

- 思考：redis只能存5G数据，可是你写了10G，那会删5G的数据? 怎么删的？你的数据已经设置了过期时间，但是时间到了，内存占用率还是比较高，有思考过原因么?

redis采用的是定期删除+惰性删除策略

### 1 定时删除策略

定时删除,用一个定时器来负责监视key,过期则自动删除。虽然内存及时释放，但是十分消耗CPU资源。在大并发请求下，CPU要将时间应用在处理请求，而不是删除key,因此没有采用这一策略.

### 2 定期删除+惰性删除

- 定期删除，redis默认每个100ms检查，是否有过期的key,有过期key则删除。需要说明的是，redis不是每个100ms将所有的key检查一次，而是随机抽取进行检查(如果每隔100ms,全部key进行检查，redis岂不是卡死)。因此，如果只采用定期删除策略，会导致很多key到时间没有删除。

- 于是，惰性删除派上用场。也就是说在你获取某个key的时候，redis会检查一下，这个key如果设置了过期时间那么是否过期了？如果过期了此时就会删除。

问题点：

如果定期删除没删除key。然后你也没即时去请求key，也就是说惰性删除也没生效。这样，redis的内存会越来越高。那么就应该采用内存淘汰机制。
在redis.conf中有一行配置

```
# maxmemory-policy allkeys-lru
```

内存淘汰策略方式

- 1）noeviction：当内存不足以容纳新写入数据时，新写入操作会报错。应该没人用吧。
- 2）allkeys-lru：当内存不足以容纳新写入数据时，在键空间中，移除最近最少使用的key。推荐使用。
- 3）allkeys-random：当内存不足以容纳新写入数据时，在键空间中，随机移除某个key。应该也没人用吧，你不删最少使用Key,去随机删。
- 4）volatile-lru：当内存不足以容纳新写入数据时，在设置了过期时间的键空间中，移除最近最少使用的key。这种情况一般是把redis既当缓存，又做持久化存储的时候才用。不推荐
- 5）volatile-random：当内存不足以容纳新写入数据时，在设置了过期时间的键空间中，随机移除某个key。依然不推荐
- 6）volatile-ttl：当内存不足以容纳新写入数据时，在设置了过期时间的键空间中，有更早过期时间的key优先移除。不推荐

>ps：如果没有设置 expire 的key, 不满足先决条件(prerequisites); 那么 volatile-lru, volatile-random 和 volatile-ttl 策略的行为, 和 noeviction(不删除) 基本上一致。


## 六 缓存穿透和缓存雪崩问题

### 6.1 缓存穿透

即黑客故意去请求缓存中不存在的数据，导致所有的请求都怼到数据库上，从而数据库连接异常。
 
解决方案

- (一)利用互斥锁，缓存失效的时候，先去获得锁，得到锁了，再去请求数据库。没得到锁，则休眠一段时间重试

- (二)采用异步更新策略，无论key是否取到值，都直接返回。value值中维护一个缓存失效时间，缓存如果过期，异步起一个线程去读数据库，更新缓存。需要做缓存预热(项目启动前，先加载缓存)操作。

- (三)提供一个能迅速判断请求是否有效的拦截机制，比如，利用`布隆过滤器`，内部维护一系列合法有效的key。迅速判断出，请求所携带的Key是否合法有效。如果不合法，则直接返回。

### 6.2 缓存雪崩

即缓存同一时间大面积的失效，这个时候又来了一波请求，结果请求都怼到数据库上，从而导致数据库连接异常。

解决方案

- (一)给缓存的失效时间，加上一个随机值，避免集体失效。

- (二)使用互斥锁，但是该方案吞吐量明显下降了。

- (三) 双缓存。我们有两个缓存，缓存A和缓存B。缓存A的失效时间为20分钟，缓存B不设失效时间。自己做缓存预热操作。然后细分以下几个小点

    - 从缓存A读数据库，有则直接返回

    - A没有数据，直接从B读数据，直接返回，并且异步启动一个更新线程。

    - 更新线程同时更新缓存A和缓存B。

## 七 redis 持久化

`Redis持久化`,就是将`内存数据`保存到硬盘，Redis 持久化存储分为 `RDB` 与 `AOF` 两种模式，默认开启rdb。

### 7.1 RDB持久化

RDB 是在某个时间点将数据写入一个临时文件`dump.rdb`，持久化结束后，用这个临时文件替换上次持久化的文件，达到数据恢复，采用二进制文件形式进行存储。

- `优点`：使用`单独子进程`来进行`持久化`，`主进程`不会进行任何 `IO 操作`，保证了redis 的`高性能`。
- `缺点`：RDB 是`间隔一段时间`进行`持久化`，如果持久化之间 redis `发生故障`，`会发生数据丢失`。所以这种方式更适合数据要求不严谨的时候。

这里说的这个执行数据写入到临时文件的时间点是可以通过配置来自己确定的，通过配置redis 在 `n 秒内`如果`超过 m 个 key 被修改`这执行一次 `RDB 操作`。这个操作就类似于在这个时间点来`保存一次Redis的所有数据`，`一次快照数据`。所有这个持久化方法也通常叫做`snapshots`。

RDB 默认开启，redis.conf 中的具体配置参数如下：

```shell

#dbfilename：持久化数据存储在本地的文件
dbfilename dump.rdb
#dir：持久化数据存储在本地的路径，如果是在/redis/redis-3.0.6/src下启动的redis-cli，则数据会存储在当前src目录下
dir ./
##snapshot触发的时机，save    
##如900秒后，至少有一个变更操作，才会snapshot  
##对于此值的设置，需要谨慎，评估系统的变更操作密集程度  
##可以通过"save """来关闭snapshot功能  
#save时间，以下分别表示更改了1个key时间隔900s进行持久化存储；更改了10个key300s进行存储；更改10000个key60s进行存储。
save 900 1
save 300 10
save 60 10000
##当snapshot时出现错误无法继续时，是否阻塞客户端"变更操作"，"错误"可能因为磁盘已满/磁盘故障/OS级别异常等  
stop-writes-on-bgsave-error yes  
##是否启用rdb文件压缩，默认为"yes"，压缩往往意味着"额外的cpu消耗"，同时也意味这较小的文件尺寸以及较短的网络传输时间  
rdbcompression yes  

```

> 注意：使用rdb模式，如果直接shutdown redis-cli，那么会在关闭后更新一次dump.rdb，因为内存中还存在redis进程，在关闭时会自动备份，但如果是直接杀死进程或直接关机，则redis不会更新dump.rdb，因为redis已从内存中消失。

> 如何进行数据备份与恢复呢？
> 实际上，数据备份与恢复跟redis的持久化息息相关。对于rdb来说，dump.rdb就是redis持久化文件，通过dump.rdb实现数据的备份和恢复，如果把dump.rdb删除，则redis中的数据将会丢失。

### 7.2 AOF持久化

`Append-only file`，将`“操作 + 数据”`以格式化指令的方式`追加`到操作日志文件的尾部`，在 append 操作返回后(已经写入到文件或者即将写入)，才进行实际的数据变更，“日志文件”保存了历史所有的操作过程；当 server 需要数据恢复时，可以直接 replay 此日志文件，即可还原所有的操作过程。`AOF 相对可靠`，它和 `mysql` 中 `bin.log`、`apache.log`、`zookeeper` 中 `txn-log` 简直异曲同工。AOF 文件内容是字符串，非常容易阅读和解析。

- 优点：可以保持更高的数据完整性，如果设置追加 file 的时间是 1s，如果 redis 发生故障，最多会丢失 1s 的数据；且如果日志写入不完整支持 redis-check-aof 来进行日志修复；aof文件没被 rewrite 之前（文件过大时会对命令进行合并重写），可以删除其中的某些命令（比如误操作的 flushall）。
- 缺点：AOF 文件比 RDB 文件大，且恢复速度慢。

我们可以简单的认为 AOF 就是`日志文件`，此文件只会记录“变更操作”(例如：set/del 等)，如果 server 中持续的大量变更操作，将会导致 AOF 文件非常的庞大，意味着 server 失效后，数据恢复的过程将会很长；事实上，一条数据经过多次变更，将会产生多条 AOF 记录，其实只要保存当前的状态，历史的操作记录是可以抛弃的；因为 AOF 持久化模式还伴生了`“AOF rewrite”`。
AOF 的特性决定了它相对比较安全，如果`你期望数据更少的丢失`，那么可以采用 AOF 模式。如果 AOF 文件正在被写入时突然 server 失效，有可能导致文件的最后一次记录是不完整，你可以通过手工或者程序的方式去检测并修正不完整的记录，以便通过 aof 文件恢复能够正常；同时需要提醒，如果你的 redis `持久化手段中有 aof`，那么在 `server 故障失效后再次启动前`，需要检测 `aof 文件的完整性`。

AOF 默认关闭，开启方法，修改配置文件 reds.conf：appendonly yes

```
##此选项为aof功能的开关，默认为"no"，可以通过"yes"来开启aof功能  
##只有在"yes"下，aof重写/文件同步等特性才会生效  
appendonly yes  
 
##指定aof文件名称  
appendfilename appendonly.aof  
 
##指定aof操作中文件同步策略，有三个合法值：always everysec no,默认为everysec  
appendfsync everysec  
##在aof-rewrite期间，appendfsync是否暂缓文件同步，"no"表示"不暂缓"，"yes"表示"暂缓"，默认为"no"  
no-appendfsync-on-rewrite no  
 
##aof文件rewrite触发的最小文件尺寸(mb,gb),只有大于此aof文件大于此尺寸是才会触发rewrite，默认"64mb"，建议"512mb"  
auto-aof-rewrite-min-size 64mb  
 
##相对于"上一次"rewrite，本次rewrite触发时aof文件应该增长的百分比。  
##每一次rewrite之后，redis都会记录下此时"新aof"文件的大小(例如A)，那么当aof文件增长到A*(1 + p)之后  
##触发下一次rewrite，每一次aof记录的添加，都会检测当前aof文件的尺寸。  
auto-aof-rewrite-percentage 100  
```

AOF 是文件操作，对于变更操作比较密集的 server，那么必将造成磁盘 IO 的负荷加重；此外 linux 对文件操作采取了“延迟写入”手段，即并非每次 write 操作都会触发实际磁盘操作，而是进入了 buffer 中，当 buffer 数据达到阀值时触发实际写入(也有其他时机)，这是 linux 对文件系统的优化，但是这却有可能带来隐患，如果 buffer 没有刷新到磁盘，此时物理机器失效(比如断电)，那么有可能导致最后一条或者多条 aof 记录的丢失。通过上述配置文件，可以得知 redis 提供了 3 中 aof 记录同步选项：

- always：每一条 aof 记录都立即同步到文件，这是最安全的方式，也以为更多的磁盘操作和阻塞延迟，是 IO 开支较大。

- everysec：每秒同步一次，性能和安全都比较中庸的方式，也是 redis 推荐的方式。如果遇到物理服务器故障，有可能导致最近一秒内 aof 记录丢失(可能为部分丢失)。

- no：redis 并不直接调用文件同步，而是交给操作系统来处理，操作系统可以根据 buffer 填充情况 / 通道空闲时间等择机触发同步；这是一种普通的文件操作方式。性能较好，在物理服务器故障时，数据丢失量会因 OS 配置有关。

### 7.3 AOF与RDB区别

- AOF 更加安全，可以将数据更加及时的同步到文件中，但是 AOF 需要较多的磁盘 IO 开支，AOF 文件尺寸较大，文件内容恢复数度相对较慢。

- RDB，安全性较差，它是“正常时期”数据备份以及 master-slave 数据同步的最佳手段，文件尺寸较小，恢复数度较快。

>可以通过配置文件来指定它们中的一种，或者同时使用它们(不建议同时使用)，或者全部禁用，在架构良好的环境中，`master 通常使用AOF`，`slave 使用rdb`，主要原因是 master 需要首先确保数据完整性，它作为数据备份的第一选择；slave 提供只读服务(目前 slave 只能提供读取服务)，它的主要目的就是`快速响应客户端 read 请求`；但是如果你的 redis 运行在网络稳定性差 / 物理环境糟糕情况下，建议你 master 和 slave 均采取 AOF，这个在 master 和 slave 角色切换时，可以减少“人工数据备份”/“人工引导数据恢复”的时间成本；如果你的环境一切非常良好，且服务需要接收密集性的 write 操作，那么建议 master采取 rdb，而 slave 采用 AOF。