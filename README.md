# bookmark

## 配置integrations [https://developers.notion.com/docs]
注册完notion之后，进入https://www.notion.so/my-integrations 新建 integrations
填写好 Name 之后 Associated workspace 选择自己的账户，然后点击 Submit
然后记录 (顶部) Secrets - Internal Integration Token [notion令牌（注意不要泄露）]

## 配置Page
进入https://www.notion.so/ 点击 Add a page
此时记录浏览器搜索栏链接[格式：https://www.notion.so/1e065fe94dab4ad69ef7d968**********]，记录域名后面一串字符[database_id]

Untitled处填写标题，下方选择 Empty ，随后输入 /data ，选择提示的 Database - Inline ，然后修改表格，如Aa Name改成title等
表格三列标题 title[默认]、url[url类型]、tab[text类型]

## 配置vercel
fork项目[https://github.com/Heyiki/bookmark]到自己的github上，然后登录vercel，新建项目-选择continue with github-然后import对应的项目
然后Environment Variables填上前面记录的notion令牌、database_id
格式：
name:NOTION_TOKEN  value:notion令牌
name:DATABASE_ID  value:database_id值

配置好之后，查看build log没问题就可以访问了[注：由于vercel的dns被污染了，目前只能在项目的setting-domains上添加自己的域名访问]

## API操作
列表操作：
域名/api?m=list  访问列表（每页默认返回10条）
域名/api?m=list&s=20  访问列表（每页返回20条）
域名/api?m=list&p=【列表返回next_cursor值】  访问列表（跳转到某一页）

创建：
域名/api?t=标题&u=链接&tb=标签  标签默认值：公共，链接必填，标题为空默认取链接赋值

编辑：
域名/api?pid=列表对应的id值&t=标题&u=链接&tb=标签   pid必填，标题、链接、标签有填写才修改

删除：
域名/api?pid=列表对应的id值     pid必填