import {Message} from 'element-ui';
export const reg = {
  checkName:function(name){
    var reg = /^[\u4e00-\u9fa5]+$/;
    if(name == ''){
      Message.error('姓名不能为空')
    }else if(!reg.test(name)){
      Message.error('姓名必须输入合法中文')
      return false
    }else{
      return true
    }
  },
  checkPwd:function(password){
    var reg =  /^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/g;
    if(reg.test(password)){
      return true
    }else{
      Message.error('密码必须由6-18位字母数字组合')
      return false
    }
  },
  confirmPwd:function(password,confirm_password){
    if(password===confirm_password){
      return true
    }else{
      Message.error('新密码两次输入不同')
      return false
    }
  }
}
