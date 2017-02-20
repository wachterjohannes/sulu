define(["jquery","underscore","config","services/suluarticle/article-manager","sulusecurity/services/user-manager","services/sulupreview/preview","sulusecurity/services/security-checker","sulucontent/components/copy-locale-overlay/main","sulucontent/components/open-ghost-overlay/main"],function(a,b,c,d,e,f,g,h,i){"use strict";return{defaults:{options:{config:{}},templates:{url:"/admin/api/articles<% if (!!id) { %>/<%= id %><% } %>?locale=<%= locale %>"},translations:{headline:"sulu_article.edit.title",draftLabel:"sulu-document-manager.draft-label",removeDraft:"sulu-content.delete-draft",unpublish:"sulu-document-manager.unpublish",unpublishConfirmTextNoDraft:"sulu-content.unpublish-confirm-text-no-draft",unpublishConfirmTextWithDraft:"sulu-content.unpublish-confirm-text-with-draft",unpublishConfirmTitle:"sulu-content.unpublish-confirm-title",deleteDraftConfirmTitle:"sulu-content.delete-draft-confirm-title",deleteDraftConfirmText:"sulu-content.delete-draft-confirm-text",openGhostOverlay:{info:"sulu_article.settings.open-ghost-overlay.info","new":"sulu_article.settings.open-ghost-overlay.new",copy:"sulu_article.settings.open-ghost-overlay.copy",ok:"sulu_article.settings.open-ghost-overlay.ok"},copyLocaleOverlay:{info:"sulu_article.settings.copy-locale-overlay.info"}}},layout:function(){return{navigation:{collapsed:!0},content:{shrinkable:!!this.options.id},sidebar:!!this.options.id&&"max"}},header:function(){var a={},d={},e={};return g.hasPermission(this.data,"edit")&&(e.saveDraft={},g.hasPermission(this.data,"live")&&(e.savePublish={},e.publish={}),c.has("sulu_automation.enabled")&&(e.automationInfo={options:{entityId:this.options.id,entityClass:"Sulu\\Bundle\\ArticleBundle\\Document\\ArticleDocument",handlerClass:["Sulu\\Bundle\\ContentBundle\\Automation\\DocumentPublishHandler","Sulu\\Bundle\\ContentBundle\\Automation\\DocumentUnpublishHandler"]}}),a.save={parent:"saveWithDraft",options:{callback:function(){this.sandbox.emit("sulu.toolbar.save","publish")}.bind(this),dropdownItems:e}},a.template={options:{dropdownOptions:{url:"/admin/articles/templates?type="+(this.options.type||this.data.articleType),callback:function(a){this.template=a.template,this.sandbox.emit("sulu.tab.template-change",a)}.bind(this)}}}),g.hasPermission(this.data,"live")&&(d.unpublish={options:{title:this.translations.unpublish,disabled:!this.data.published,callback:this.unpublish.bind(this)}},d.divider={options:{divider:!0}}),g.hasPermission(this.data,"delete")&&(d["delete"]={options:{disabled:!this.options.id,callback:this.deleteArticle.bind(this)}}),d.copyLocale={options:{title:this.sandbox.translate("toolbar.copy-locale"),callback:function(){h.startCopyLocalesOverlay.call(this,this.translations.copyLocaleOverlay).then(function(a){return b.contains(a,this.options.locale)?void this.toEdit(this.options.locale):(this.data.concreteLanguages=b.uniq(this.data.concreteLanguages.concat(a)),void this.sandbox.emit("sulu.labels.success.show","labels.success.copy-locale-desc","labels.success"))}.bind(this))}.bind(this)}},this.sandbox.util.isEmpty(d)||(a.edit={options:{dropdownItems:d}}),a.statePublished={},a.stateTest={},{tabs:{url:"/admin/content-navigations?alias=article&id="+this.options.id+"&locale="+this.options.locale,options:{data:function(){return this.sandbox.util.deepCopy(this.data)}.bind(this),url:function(){return this.templates.url({id:this.options.id,locale:this.options.locale})}.bind(this),config:this.options.config,preview:this.preview},componentOptions:{values:b.defaults(this.data,{type:null})}},toolbar:{buttons:a,languageChanger:{data:this.options.config.languageChanger,preSelected:this.options.locale}}}},initialize:function(){this.bindCustomEvents(),this.showDraftLabel(),this.setHeaderBar(!0),this.loadLocalizations(),this.options.language=this.options.locale},bindCustomEvents:function(){this.sandbox.on("sulu.header.back",this.toList.bind(this)),this.sandbox.on("sulu.tab.dirty",this.setHeaderBar.bind(this)),this.sandbox.on("sulu.toolbar.save",this.save.bind(this)),this.sandbox.on("sulu.tab.data-changed",this.setData.bind(this)),this.sandbox.on("sulu.article.error",this.handleError.bind(this)),this.sandbox.on("husky.tabs.header.item.select",this.tabChanged.bind(this)),this.sandbox.on("sulu.header.language-changed",this.languageChanged.bind(this))},languageChanged:function(a){a.id!==this.options.locale&&(this.sandbox.sulu.saveUserSetting(this.options.config.settingsKey,a.id),-1===b(this.data.concreteLanguages).indexOf(a.id)?i.openGhost.call(this,this.data,this.translations.openGhostOverlay).then(function(b,c){b?h.copyLocale.call(this,this.data.id,c,[a.id],function(){this.toEdit(a.id)}.bind(this)):this.toEdit(a.id)}.bind(this)).fail(function(){this.sandbox.emit("sulu.header.change-language",this.options.language)}.bind(this)):this.toEdit(a.id))},tabChanged:function(a){this.options.content=a.id},handleError:function(a,b,c){switch(a){default:this.sandbox.emit("sulu.labels.error.show","labels.error.content-save-desc","labels.error"),this.sandbox.emit("sulu.header.toolbar.item.enable","save")}},deleteArticle:function(){this.sandbox.sulu.showDeleteDialog(function(a){a&&d.remove(this.options.id,this.options.locale).then(function(){this.toList()}.bind(this))}.bind(this))},toEdit:function(a,b){this.sandbox.emit("sulu.router.navigate","articles/"+(a||this.options.locale)+"/edit:"+(b||this.options.id)+"/"+(this.options.content||"details"),!0,!0)},toList:function(){1===this.options.config.typeNames.length?this.sandbox.emit("sulu.router.navigate","articles/"+this.options.locale):this.sandbox.emit("sulu.router.navigate","articles:"+(this.options.type||this.data.articleType)+"/"+this.options.locale)},toAdd:function(){1===this.options.config.typeNames.length?this.sandbox.emit("sulu.router.navigate","articles/"+this.options.locale+"/add",!0,!0):this.sandbox.emit("sulu.router.navigate","articles/"+this.options.locale+"/add:"+(this.options.type||this.data.articleType),!0,!0)},save:function(a){this.loadingSave(),this.saveTab(a).then(function(b){this.saved(b.id,b,a)}.bind(this))},setData:function(a){this.data=a},saveTab:function(c){var d=a.Deferred();return this.sandbox.emit("sulu.header.toolbar.item.loading","save"),this.sandbox.once("sulu.tab.saved",function(a,c){d.resolve(b.defaults(c,{type:null}))}.bind(this)),this.sandbox.emit("sulu.tab.save",c),d},setHeaderBar:function(a){var b=!a,c=!a,d=!!a&&!this.data.publishedState;this.setSaveToolbarItems.call(this,"saveDraft",b),this.setSaveToolbarItems.call(this,"savePublish",c),this.setSaveToolbarItems.call(this,"publish",d),this.setSaveToolbarItems.call(this,"unpublish",!!this.data.published),b||c||d?this.sandbox.emit("sulu.header.toolbar.item.enable","save",!1):this.sandbox.emit("sulu.header.toolbar.item.disable","save",!1),this.showState(!!this.data.published)},setSaveToolbarItems:function(a,b){this.sandbox.emit("sulu.header.toolbar.item."+(b?"enable":"disable"),a,!1)},loadingSave:function(){this.sandbox.emit("sulu.header.toolbar.item.loading","save")},afterSaveAction:function(a,b){"back"===a?this.toList():"new"===a?this.toAdd():b&&this.toEdit(this.options.locale,this.data.id)},showDraftLabel:function(){this.sandbox.emit("sulu.header.tabs.label.hide"),this.hasDraft(this.data)||e.find(this.data.changer).then(function(a){this.sandbox.emit("sulu.header.tabs.label.show",this.sandbox.util.sprintf(this.translations.draftLabel,{changed:this.sandbox.date.format(this.data.changed,!0),user:a.username}),[{id:"delete-draft",title:this.translations.removeDraft,skin:"critical",onClick:this.deleteDraft.bind(this)}])}.bind(this))},deleteDraft:function(){this.sandbox.sulu.showDeleteDialog(function(a){a&&(this.sandbox.emit("husky.label.header.loading"),d.removeDraft(this.data.id,this.options.locale).always(function(){this.sandbox.emit("sulu.header.toolbar.item.enable","edit")}.bind(this)).then(function(a){this.sandbox.emit("sulu.router.navigate",this.sandbox.mvc.history.fragment,!0,!0),this.saved(a.id,a)}.bind(this)).fail(function(){this.sandbox.emit("husky.label.header.reset"),this.sandbox.emit("sulu.labels.error.show","labels.error.remove-draft-desc","labels.error")}.bind(this)))}.bind(this),this.translations.deleteDraftConfirmTitle,this.translations.deleteDraftConfirmText)},hasDraft:function(a){return!a.id||!!a.publishedState||!a.published},getUrl:function(a){var c=b.template(this.defaults.templates.url,{id:this.options.id,locale:this.options.locale});return a&&(c+="&action="+a),c},loadComponentData:function(){var b=a.Deferred();return this.options.id?(this.sandbox.util.load(this.getUrl()).done(function(a){this.preview=f.initialize({}),this.preview.start("Sulu\\Bundle\\ArticleBundle\\Document\\ArticleDocument",this.options.id,this.options.locale,a),b.resolve(a)}.bind(this)),b):(b.resolve({}),b)},destroy:function(){this.preview&&f.destroy(this.preview)},showState:function(a){a?(this.sandbox.emit("sulu.header.toolbar.item.hide","stateTest"),this.sandbox.emit("sulu.header.toolbar.item.show","statePublished")):(this.sandbox.emit("sulu.header.toolbar.item.hide","statePublished"),this.sandbox.emit("sulu.header.toolbar.item.show","stateTest"))},unpublish:function(){this.sandbox.sulu.showConfirmationDialog({callback:function(a){a&&(this.sandbox.emit("sulu.header.toolbar.item.loading","edit"),d.unpublish(this.data.id,this.options.locale).always(function(){this.sandbox.emit("sulu.header.toolbar.item.enable","edit")}.bind(this)).then(function(a){this.sandbox.emit("sulu.labels.success.show","labels.success.content-unpublish-desc","labels.success"),this.saved(a.id,a)}.bind(this)).fail(function(){this.sandbox.emit("sulu.labels.error.show","labels.error.content-unpublish-desc","labels.error")}.bind(this)))}.bind(this),title:this.translations.unpublishConfirmTitle,description:this.hasDraft(this.data)?this.translations.unpublishConfirmTextNoDraft:this.translations.unpublishConfirmTextWithDraft})},saved:function(a,b,c){this.setData(b),this.options.id?(this.setHeaderBar(!0),this.showDraftLabel(),this.sandbox.emit("sulu.header.saved",b),this.sandbox.emit("sulu.labels.success.show","labels.success.content-save-desc","labels.success")):this.sandbox.sulu.viewStates.justSaved=!0,this.afterSaveAction(c,!this.options.id)},loadLocalizations:function(){this.sandbox.util.load("/admin/api/localizations").then(function(a){this.localizations=a._embedded.localizations.map(function(a){return{id:a.localization,title:a.localization}})}.bind(this))},getCopyLocaleUrl:function(a,b,c){return d.getCopyLocaleUrl(a,b,c)}}});