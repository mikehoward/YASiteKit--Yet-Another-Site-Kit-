(function(){tinymce.PluginManager.requireLangPack('imgupload');tinymce.create('tinymce.plugins.ImgUploadPlugin',{init:function(ed,url){ed.addCommand('mceImgUpload',function(){ed.windowManager.open({file:url+'/dialog.htm',width:400+parseInt(ed.getLang('imgupload.delta_width',0)),height:170+parseInt(ed.getLang('imgupload.delta_height',0)),inline:1},{plugin_url:url,jQuery:window.jQuery||null,})});ed.addButton('imgupload',{title:'imgupload.desc',cmd:'mceImgUpload',image:url+'/img/imgupload.gif',})},createControl:function(n,cm){return null},getInfo:function(){return{longname:'ImgUpload plugin',author:'Mike Howard',authorurl:'http://www.clove.com.com',infourl:'http://www.clove.com',version:"1.0.0-alpha2"}}});tinymce.PluginManager.add('imgupload',tinymce.plugins.ImgUploadPlugin)})();