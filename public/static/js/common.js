(function (window) {
    'use strict';

    // Vue 3 全局 Mixin
    const globalMixin = {
        data() {
            return {
                loading: false
            };
        },
        methods: {
            load() {
                layer.load(0);
            },
            closeLoad() {
                layer.closeAll();
            },
            post(url, data, cb) {
                if (typeof url === 'object') {
                    url = '';
                }
                this.load();
                $.post(url, data, res => {
                    this.closeLoad();
                    cb ? cb(res) : this.alert(res);
                });
            },
            ajax(url, data, cb) {
                if (typeof url === 'object') {
                    url = '';
                }
                this.load();
                $.ajax({
                    url: url,
                    data: data,
                    type: 'POST',
                    dataType: 'JSON',
                    cache: false,
                    processData: false,
                    contentType: false,
                    success: res => {
                        this.closeLoad();
                        cb && cb(res);
                    }
                });
            },
            alert(res, cb) {
                console.log(res.msg);
                Swal.fire({
                    title: res.code == 1 ? 'success' : 'error',
                    type: res.code == 1 ? 'success' : 'error',
                    html: res.msg,
                    allowOutsideClick: false,
                    customClass: {
                        confirmButton: 'btn' + (res.code == 1 ? ' btn-primary' : ' btn-danger')
                    }
                }).then(function (isConfirm) {
                    if (isConfirm.value === true) {
                        cb && cb(res);
                        if (res.url !== undefined && res.url) {
                            window.location.href = res.url;
                        }
                    }
                });
            },
            recursion(data, list, key) {
                for (let k in list) {
                    let keykk = key + '[' + k + ']';
                    if (typeof list[k] === 'object') {
                        if (list[k] instanceof File) {
                            console.log(k);
                            data.append(keykk, list[k]);
                        } else {
                            this.recursion(data, list[k], keykk);
                        }
                    } else {
                        data.append(keykk, list[k]);
                    }
                }
            },
            submit(url) {
                this.confirm('确认要提交吗', () => {
                    let data = new FormData();
                    for (let k in this.form) {
                        if (typeof this.form[k] === 'object') {
                            if (this.form[k] instanceof File) {
                                data.append(k, this.form[k]);
                            } else {
                                this.recursion(data, this.form[k], k);
                            }
                        } else {
                            data.append(k, this.form[k]);
                        }
                    }
                    this.ajax('', data, res => {
                        if (res.msg) {
                            this.alert(res, () => {
                                if (res.code == 1) {
                                    if (res.url !== undefined && res.url == '') {
                                        location.reload();
                                    } else if (res.url !== undefined && res.url) {
                                        window.location.href = res.url;
                                    } else {
                                        history.back();
                                    }
                                }
                            });
                        }
                    });

                })
            },
            nonce_str(len) {
                len = len || 32;
                var $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';
                var maxPos = $chars.length;
                var pwd = '';
                for (let i = 0; i < len; i++) {
                    pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
                }
                return pwd;
            },
            confirm(msg, cb) {
                Swal.fire({
                    title: 'warning',
                    type: 'error',
                    html: msg,
                    showCancelButton: true,
                    allowOutsideClick: false,
                    confirmButtonText: 'OK',
                    cancelButtonText: 'cancel'
                }).then(function (isConfirm) {
                    if (isConfirm.value === true) {
                        cb && cb();
                    }
                });
            },
            del(where, url = 'del') {
                this.confirm('确定要删除吗???', () => {
                    this.post(url, where);
                });
            },
            prompt(queue, cb) {
                let _this = this;
                let progressSteps = [];
                for (let k in queue) {
                    progressSteps[k] = parseInt(k) + 1;
                }
                Swal.mixin({
                    allowOutsideClick: false,
                    input: 'text',
                    confirmButtonText: 'OK',
                    showCancelButton: true,
                    cancelButtonText: 'cancel',
                    progressSteps
                }).queue(queue).then(function (result) {
                    if (result.dismiss)
                        return;
                    for (let k in result.value) {
                        if (result.value[k] == '') {
                            _this.alert({ code: 0, msg: queue[k] });
                            return;
                        }
                    }
                    cb && cb(result.value);
                });
            },
            getObjectURL(file) {
                var url = null;
                if (window.createObjectURL != undefined) {
                    url = window.createObjectURL(file);
                } else if (window.URL != undefined) {
                    url = window.URL.createObjectURL(file);
                } else if (window.webkitURL != undefined) {
                    url = window.webkitURL.createObjectURL(file);
                }
                return url;
            }
        }
    };

    // 创建 Vue 应用的辅助函数
    window.createVueApp = function (options = {}) {
        if (typeof Vue === 'undefined') {
            console.error('Vue 3 未加载，请先引入 vue3.global.js');
            return null;
        }

        const { createApp } = Vue;

        // 合并全局 mixin
        const app = createApp({
            ...options,
            mixins: [globalMixin, ...(options.mixins || [])]
        });

        return app;
    };

})(window);
