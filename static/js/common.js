//自动加载
var p = 2;
var tmp = 0;
$(window).scroll(function() {
	var scrollTop = $(this).scrollTop();
	var scrollHeight = $(document).height();
	var windowHeight = $(this).height();
	if (scrollTop + windowHeight == scrollHeight) {
		if (tmp != p) {
			//业务需求
			//.....
			//.....
			//.....
			//.....
			//.....
		}
	}
});

//通用form提交
$("form").submit(function () {
	var data = $("form").serialize();
	layer.confirm("您确定要提交申请？", {icon: 3}, function(){
		http_request_post("", data, function (result) {
			layer.msg(result.msg, {icon:1, time:800}, function (){
				if (result.data != "") {
					location.href = result.data;
				} else {
					location.reload();
				}
			});
		});
	});
	return false;
})

//通用post请求
function http_request_post(url, data, callback, type) {
	$.ajax({
		type : 'post',  
		url : url,
		dataType : 'json',
		data : data,
		success : function(repones) {
			if (type == "all") {
				//原样返回
				if (typeof callback === "function") {
					callback(repones);
					return false;
				} else {
					layer.msg('请求加载数据失败，请重新加载');
				}
			} else {
				if (repones.status == 1) {
					if (typeof callback === "function") {
						callback(repones);
						return false;
					} else {
						layer.msg('请求加载数据失败，请重新加载');
					}
				} else {
					layer.msg(repones.msg);
					return false;
				}
			}
		},
		error : function () {
			return false;
		}
	});
	return false;
}