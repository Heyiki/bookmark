# bookmark

## 配置 integrations [https://developers.notion.com/docs]

注册完 notion 之后，进入https://www.notion.so/my-integrations 新建 integrations

填写好 Name 之后 Associated workspace 选择自己的账户，然后点击 Submit

然后记录 (顶部) `Secrets` - `Internal Integration Token` [notion 令牌（注意不要泄露）]

## 配置 Page

进入https://www.notion.so/ 点击 Add a page
此时记录浏览器搜索栏链接`格式：https://www.notion.so/1e065fe94dab4ad69ef7d968**********`，记录域名后面一串字符`database_id`

Untitled 处填写标题，下方选择 Empty ，随后输入 `/data` ，选择提示的 `Database - Inline` ，然后修改表格，如 Aa Name 改成 title 等
表格三列标题 title(默认)、url(url 类型)、tab(text 类型)

## 配置 vercel

fork 项目`https://github.com/Heyiki/bookmark`到自己的 github 上，然后登录 vercel，新建项目-选择 continue with github-然后 import 对应的项目
然后 Environment Variables 填上前面记录的 `notion 令牌`、`database_id`

格式：

name:`NOTION_TOKEN` value:notion 令牌

name:`DATABASE_ID` value:database_id 值

配置好之后，查看 build log 没问题就可以访问了[注：由于国内 vercel 的 dns 被污染了，目前只能在项目的 setting-domains 上添加自己的域名访问]

## API 操作

#### 列表操作：

域名/api?m=rows 访问列表（每页默认返回 10 条）

域名/api?m=rows&s=20 访问列表（每页返回 20 条）

域名/api?m=rows&p=【列表返回 next_cursor 值】 访问列表（跳转到某一页）

域名/api?m=rows&tf=筛选标题

域名/api?m=rows&uf=筛选链接

域名/api?m=rows&tbf=筛选标签

域名/api?m=rows&tf=筛选标题&tbf=筛选标签

#### 创建：

域名/api?t=标题&u=链接&tb=标签 标签默认值：公共，链接必填，标题为空默认取链接赋值

#### 编辑：

域名/api?pid=列表对应的 id 值&t=标题&u=链接&tb=标签 pid 必填，标题、链接、标签有填写才修改

#### 删除：

域名/api?pid=列表对应的 id 值 pid 必填
