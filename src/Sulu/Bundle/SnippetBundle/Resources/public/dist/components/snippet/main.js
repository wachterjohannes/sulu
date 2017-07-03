define(["sulusnippet/model/snippet","app-config"],function(a,b){"use strict";var c="contentLanguage",d=function(){},e={deleteReferencedByFollowing:"snippet.delete-referenced-by-following",deleteConfirmText:"snippet.delete-confirm-text",deleteConfirmDefaultText:"snippet.delete-confirm-default-text",deleteConfirmTitle:"snippet.delete-confirm-title",deleteDoIt:"snippet.delete-do-it",deleteNoSnippetsSelected:"snippet.delete-no-snippets-selected"},f={contentChanged:1102},g={referentialIntegrityMessage:function(a,b){var c=[];return a.length>0&&(c.push("<p>",this.sandbox.translate(e.deleteReferencedByFollowing),"</p>"),c.push("<ul>"),this.sandbox.util.foreach(a,function(a){c.push("<li>",a,"</li>")}),c.push("</ul>")),b&&c.push("<p>",this.sandbox.translate(e.deleteConfirmDefaultText),"</p>"),c.push("<p>",this.sandbox.translate(e.deleteConfirmText),"</p>"),c.join("")}};return d.prototype={translations:{openGhostOverlay:{info:"snippet.settings.open-ghost-overlay.info","new":"snippet.settings.open-ghost-overlay.new",copy:"snippet.settings.open-ghost-overlay.copy",ok:"snippet.settings.open-ghost-overlay.ok"},copyLocaleOverlay:{info:"snippet.settings.copy-locale-overlay.info"}},bindModelEvents:function(){this.sandbox.on("sulu.snippets.snippet.delete",this.del,this),this.sandbox.on("sulu.snippets.snippet.save",this.save,this),this.sandbox.on("sulu.snippets.snippet.load",this.load,this),this.sandbox.on("sulu.snippets.snippet.new",this.add,this),this.sandbox.on("sulu.snippets.snippets.delete",this.delSnippets,this),this.sandbox.on("sulu.snippets.snippet.list",function(a){var b="snippet/snippets";a&&(b+="/"+a),this.sandbox.emit("sulu.router.navigate",b)},this),this.sandbox.on("sulu.header.language-changed",function(a){if(this.sandbox.sulu.saveUserSetting(c,a.id),"edit"===this.type){var b=this.model.toJSON();this.sandbox.emit("sulu.snippets.snippet.load",b.id,a.id)}else"add"===this.type?this.sandbox.emit("sulu.snippets.snippet.new",a.id):this.sandbox.emit("sulu.snippets.snippet.list",a.id)},this)},del:function(){this.sandbox.sulu.showDeleteDialog(function(a){a&&(this.destroySnippet(this.model,function(){this.sandbox.emit("sulu.router.navigate","snippet/snippets")}.bind(this)),this.sandbox.emit("sulu.header.toolbar.item.loading","settings"))}.bind(this))},destroySnippet:function(a,b){a.destroy({success:function(){b()}.bind(this),error:function(c,d){409==d.status&&this.referentialIntegrityDialog(a,d.responseJSON,b)}.bind(this)})},referentialIntegrityDialog:function(a,b,c){var d=[];this.sandbox.util.foreach(b.structures,function(a){d.push(a.title)});var f=$("<div/>");$("body").append(f),this.sandbox.start([{name:"overlay@husky",options:{el:f,openOnStart:!0,title:this.sandbox.translate(e.deleteConfirmTitle),message:g.referentialIntegrityMessage.call(this,d,b.isDefault),okDefaultText:this.sandbox.translate(e.deleteDoIt),type:"alert",closeCallback:function(){},okCallback:function(){a.destroy({headers:{SuluForceRemove:!0},success:function(){c()}.bind(this)})}.bind(this)}}])},handleErrorContentChanged:function(a,b){this.sandbox.emit("sulu.overlay.show-warning","snippet.changed-warning.title","snippet.changed-warning.description",function(){this.sandbox.emit("sulu.snippets.snippet.save-error")}.bind(this),function(){this.saveSnippet(a,b,!0)}.bind(this),{okDefaultText:"snippet.changed-warning.ok-button"})},handleError:function(a,b,c){switch(a){case f.contentChanged:this.handleErrorContentChanged(b,c);break;default:this.sandbox.emit("sulu.labels.error.show","labels.error.content-save-desc","labels.error"),this.sandbox.emit("sulu.snippets.snippet.save-error")}},afterSaveAction:function(a,b){"back"===a?this.sandbox.emit("sulu.snippets.snippet.list"):"new"===a?this.sandbox.emit("sulu.router.navigate","snippet/snippets/"+this.options.language+"/add",!0,!0):this.data.id||this.sandbox.emit("sulu.router.navigate","snippet/snippets/"+this.options.language+"/edit:"+b.id+"/details")},saveSnippet:function(a,b,c){this.model.set(a),this.model.fullSave(this.options.language,null,{},{success:function(a){var c=a.toJSON();this.data.id&&this.sandbox.emit("sulu.snippets.snippet.saved",c),this.afterSaveAction(b,c)}.bind(this),error:function(c,d){this.handleError.call(this,d.responseJSON.code,a,b)}.bind(this)},c)},save:function(a,c){if(this.sandbox.emit("sulu.header.toolbar.item.loading","save"),this.template)a.template=this.template;else{var d=b.getSection("sulu-snippet");a.template=d.defaultType}this.saveSnippet(a,c)},load:function(a,b,c){b||(b=this.options.language),this.sandbox.emit("sulu.router.navigate","snippet/snippets/"+b+"/edit:"+a+"/details",void 0,void 0,c)},add:function(a){a||(a=this.options.language),this.sandbox.emit("sulu.router.navigate","snippet/snippets/"+a+"/add")},delSnippets:function(b){if(b.length<1)return void this.sandbox.emit("sulu.dialog.error.show",this.sandbox.translate(e.deleteNoSnippetsSelected));var c=function(){var d=b.shift();if(void 0!==d){var e=new a({id:d});this.destroySnippet(e,function(){this.sandbox.emit("husky.datagrid.record.remove",d),c()}.bind(this))}}.bind(this);this.sandbox.sulu.showDeleteDialog(function(a){a&&c()}.bind(this))},getCopyLocaleUrl:function(a,b,c){return["/admin/api/snippets/",a,"?language=",b,"&dest=",c,"&action=copy-locale"].join("")}},d});