<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>BookMark - Heyiki</title>
    <meta name="description" content="Heyiki bookmark">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.heyiki.top">
    <meta property="og:description" content="Heyiki bookmark">
    <meta property="article:author" content="Heyiki">
    <meta property="article:tag" content="notion,vercel,github,heyiki,bookmark,butterfly">
    <script src="https://cdn.bootcss.com/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.min.js"></script>
    <script src="https://cdn.staticfile.org/vue-resource/1.5.1/vue-resource.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/element-ui/lib/theme-chalk/index.css">
    <script src="https://cdn.jsdelivr.net/npm/element-ui/lib/index.js"></script>
</head>
<body>
    <div id="app"><App /></div>
</body>
<script>
    const api = '<?php echo $_ENV['API_URL'] ?? '' ?>';
    (function (v,w,d,q,_self){
        class page{
            constructor(...params) {
                try{
                    this.api = api
                }catch(e){
                    this.api = "https://bookmark-tan.vercel.app/api"
                }
                this.loading = false
                return this.initApp()
            }
            initApp(){
                _self = this
                this.registeredApp()
                this.app = new v({el : "#app",})
                return false
            }
            registeredApp(){
                this.registered()
                v.component("App",{
                    template:`
<div class="container">

<el-row style="margin-bottom: 2rem">
  <el-row :gutter="10">
    <el-col :xs="24" :sm="12" :md="6" :lg="6" :xl="4" style="margin-bottom: 1rem">
      <el-input placeholder="筛选标题" v-model="listSearch.tf" clearable></el-input>
    </el-col>
    <el-col :xs="24" :sm="12" :md="6" :lg="6" :xl="4" style="margin-bottom: 1rem">
      <el-input placeholder="筛选链接" v-model="listSearch.uf" clearable></el-input>
    </el-col>
    <el-col :xs="24" :sm="12" :md="6" :lg="6" :xl="4" style="margin-bottom: 1rem">
      <el-input placeholder="筛选标签" v-model="listSearch.tbf" clearable></el-input>
    </el-col>
    <el-col :xs="24" :sm="12" :md="6" :lg="6" :xl="4" style="margin-bottom: 1rem">
      <el-select v-model="listSearch.s" placeholder="请选择每页数量">
        <el-option label="每页10条" value="10"></el-option>
        <el-option label="每页20条" value="20"></el-option>
        <el-option label="每页50条" value="50"></el-option>
        <el-option label="每页100条" value="100"></el-option>
      </el-select>
    </el-col>
    <el-col :xs="24" :sm="12" :md="6" :lg="6" :xl="4" style="margin-bottom: 1rem">
      <el-button type="primary" icon="el-icon-search" @click="searchOperate">搜索</el-button>
      <el-button type="success" icon="el-icon-circle-plus-outline" @click="addOperate">添加</el-button>
    </el-col>
    <el-col :xs="24" :sm="12" :md="6" :lg="6" :xl="4" style="margin-bottom: 1rem">
      <el-button @click="firstPage" type="primary" plain icon="el-icon-d-arrow-left">第一页</el-button>
      <el-button v-if="next_cursor" @click="nextPage" type="primary">下一页<i class="el-icon-arrow-right el-icon--right"></i></el-button>
    </el-col>
  </el-row>
</el-row>

<template v-if="loading">
  <el-skeleton :rows="6" animated />
</template>
<template v-else>
  <section v-if="list.length > 0">
    <el-row :gutter="10">
      <el-col :xs="24" :sm="12" :md="6" :lg="6" :xl="4" v-for="(item,index) in list" :key="index" style="margin-bottom: 1rem">
        <el-tooltip class="item" effect="dark" :content="item.title" placement="bottom">
          <el-card shadow="hover">
            <el-link icon="el-icon-link" :href="item.url" type="primary" :underline="false">
              <span style="display:inline-block;width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ item.title }}</span>
            </el-link>
            <el-link icon="el-icon-edit" :underline="false" @click="editOperate(item.id)">编辑</el-link>
            <el-popconfirm title="确定删除吗？" @confirm="deleteOperate(item.id)">
              <el-link icon="el-icon-delete" slot="reference" :underline="false">删除</el-link>
            </el-popconfirm>
            <br>
            <el-tag type="success" size="mini">{{ item.tab }}</el-tag>
          </el-card>
        </el-tooltip>
      </el-col>
    </el-row>
  </section>
  <section v-else>
    <el-empty description="Empty data"></el-empty>
  </section>
</template>


<el-dialog :title="dialogFormTitle" :visible.sync="dialogFormVisible">
  <el-form :model="form">
    <el-form-item label="标题" :label-width="formLabelWidth">
      <el-input v-model="form.title" autocomplete="off"></el-input>
    </el-form-item>
    <el-form-item label="链接" :label-width="formLabelWidth">
      <el-input v-model="form.url" autocomplete="off"></el-input>
    </el-form-item>
    <el-form-item label="标签" :label-width="formLabelWidth">
      <el-input v-model="form.tab" autocomplete="off"></el-input>
    </el-form-item>
  </el-form>
  <div slot="footer" class="dialog-footer">
    <el-button @click="dialogFormVisible = false">取 消</el-button>
    <el-button type="primary" @click="addAndEditOperate">确 定</el-button>
  </div>
</el-dialog>

</div>
                `,
                    data(){
                        return {
                            loading : true,
                            list: [],
                            next_cursor: null,
                            form: {
                                id: '',
                                title: '',
                                link: '',
                                tab: '',
                            },
                            dialogFormTitle: '',
                            dialogFormVisible: false,
                            formLabelWidth: '80px',
                            listSearch: {
                                m: 'rows',
                                s: '',
                                tf: '',
                                uf: '',
                                tbf: '',
                                p: '',
                            },
                        }
                    },
                    created(){
                        this.getList(this.listSearch)
                    },
                    methods:{
                        searchOperate() {
                            this.listSearch.p = ''
                            this.getList(this.listSearch)
                        },
                        firstPage() {
                            this.searchOperate()
                        },
                        nextPage() {
                            this.listSearch.p = this.next_cursor
                            this.getList(this.listSearch)
                        },
                        getList(params){
                            if (!params.s) {
                                params.s = 10
                            }
                            const loading = this.$loading({
                                lock: true,
                                text: 'Loading',
                                spinner: 'el-icon-loading',
                                background: 'rgba(0, 0, 0, 0.7)'
                            });
                            this.$http.get(_self.api,{params:params}).then(res => {
                                loading.close();
                                if (res.status !== 200 && res.body.data.code !== 200) {
                                    this.$notify.error({
                                        title: 'Error',
                                        message: 'List request error'
                                    })
                                    return false;
                                }
                                this.list = res.body.data.list
                                this.next_cursor = res.body.data.next_cursor ?? null
                                this.loading = false
                            })
                        },
                        addOperate() {
                            this.dialogFormTitle = "添加"
                            this.form = {}
                            this.dialogFormVisible = true
                        },
                        editOperate(id) {
                            this.form = {}
                            this.dialogFormVisible = true
                            this.dialogFormTitle = "编辑"
                            this.$http.get(_self.api,{params:{m:"detail", pid:id}}).then(res => {
                                if (res.status !== 200 && res.body.data.code !== 200) {
                                    this.dialogFormVisible = false
                                    this.$notify.error({
                                        title: 'Error',
                                        message: 'Updated failed'
                                    })
                                } else {
                                    this.form = res.body.data
                                }
                            })
                        },
                        addAndEditOperate() {
                            if (this.form.id) {
                                this.$http.get(_self.api,{
                                    params:{
                                        m:"edit",
                                        pid:this.form.id,
                                        t:this.form.title,
                                        u:this.form.url,
                                        tb:this.form.tab
                                    }
                                }).then(res => {
                                    if (res.status !== 200 && res.body.data.code !== 200) {
                                        this.$notify.error({
                                            title: 'Error',
                                            message: 'Updated failed'
                                        })
                                    } else {
                                        this.getList()
                                        this.dialogFormVisible = false
                                        this.$notify({
                                            title: 'Success',
                                            message: 'Updated successfully',
                                            type: 'success'
                                        })
                                    }
                                })
                            } else {
                                this.$http.get(_self.api,{
                                    params:{
                                        m:"create",
                                        t:this.form.title,
                                        u:this.form.url,
                                        tb:this.form.tab
                                    }
                                }).then(res => {
                                    if (res.status !== 200 && res.body.data.code !== 200) {
                                        this.$notify.error({
                                            title: 'Error',
                                            message: 'Created failed'
                                        })
                                    } else {
                                        this.getList()
                                        this.dialogFormVisible = false
                                        this.$notify({
                                            title: 'Success',
                                            message: 'Created successfully',
                                            type: 'success'
                                        })
                                    }
                                })
                            }
                        },
                        deleteOperate(pid) {
                            this.$http.get(_self.api,{params:{m:"delete",pid:pid}}).then(res => {
                                if (res.status !== 200 && res.body.data.code !== 200) {
                                    this.$notify.error({
                                        title: 'Error',
                                        message: 'Delete failed'
                                    })
                                } else {
                                    this.getList()
                                    this.$notify({
                                        title: 'Success',
                                        message: 'Delete successfully',
                                        type: 'success'
                                    })
                                }
                            })
                        },
                    }
                })
            }
            registered(){
            }
        }
        new page();
    }(Vue,window,document,$,{}))
</script>
</html>
