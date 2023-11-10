export default {
  install: function (Vue) {
    //时间格式化时期
    Vue.prototype.formatDate = function (row, column, cellValue) {
      if (cellValue === null) {
        return ''
      } else {
        let date = ''
        if (cellValue) {
          date = new Date(cellValue)
        } else if (!row) {
          return ''
        } else {
          date = new Date(row)
        }
        let year = date.getFullYear(),
          month = date.getMonth() + 1,//月份是从0开始的
          day = date.getDate(),
          hour = date.getHours(),
          min = date.getMinutes(),
          sec = date.getSeconds();
        let newTime = year + '-' +
          (month < 10 ? '0' + month : month) + '-' +
          (day < 10 ? '0' + day : day) + ' ' +
          (hour < 10 ? '0' + hour : hour) + ':' +
          (min < 10 ? '0' + min : min) + ':' +
          (sec < 10 ? '0' + sec : sec);
        return newTime;
      }
    }
    //时间格式化时分秒
    Vue.prototype.formatDateDay = function (row, column, cellValue) {
      if (cellValue === null) {
        return ''
      } else {
        let date = ''
        if (cellValue) {
          date = new Date(cellValue)
        } else {
          date = new Date(row)
        }
        let year = date.getFullYear(),
          month = date.getMonth() + 1,//月份是从0开始的
          day = date.getDate();
        let newTime = year + '-' +
          (month < 10 ? '0' + month : month) + '-' +
          (day < 10 ? '0' + day : day)

        return newTime;
      }

    }
    //手机号脱敏
    Vue.prototype.transPhone = function (row, column, cellValue) {
      if (typeof cellValue == 'string') {
        let reg = 11 && /^((13|14|15|16|17|18|19)[0-9]{1}\d{8})$/;//手机号正则验证
        if (reg.test(cellValue.substring(0, 11))) {
          return cellValue.substring(0, 3) + '****' + cellValue.substring(7, cellValue.length)
        } else {
          return cellValue
        }
      }
    }

  }
}