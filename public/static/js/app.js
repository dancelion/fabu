;(function () {
    $('.release').click(function (e) {
        let id= $(this).data('id');
        $.post("/apps/index/release", {id:id},
            function (data, textStatus, jqXHR) {
                console.info(data);
                if(data.code){
                    noty({
                        text: data.msg,
                        type: 'success',
                        layout: 'topCenter',
                        modal: true,
                        timeout: 800,
                        callback: {
                            afterClose: function () {
                                reloadPage(window);
                            }
                        }
                    }).show();
                }else{
                    art.dialog({
                        id: 'warning',
                        icon: 'warning',
                        content: '发布失败',
                        cancelVal: '关闭',
                        cancel: function () {
                            reloadPage(window);
                        },
                        ok: function () {
                            reloadPage(window);
                        }
                    });
                }
            },
            "json"
        );
    })
})()