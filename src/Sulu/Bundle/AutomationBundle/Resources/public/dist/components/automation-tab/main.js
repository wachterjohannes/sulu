define(["underscore","services/suluautomation/task-manager","text!./skeleton.html","text!/admin/api/tasks/fields"],function(a,b,c,d){"use strict";for(var e=JSON.parse(d),f=JSON.parse(d),g=0,h=f.length;h>g;g++)"status"===f[g].name&&(f[g].disabled=!1,f[g]["default"]=!0);return{defaults:{options:{entityClass:null,locale:null,idKey:"id"},templates:{skeleton:c},translations:{headline:"sulu_automation.automation",tasks:"sulu_automation.tasks",taskHistory:"sulu_automation.task-history",successLabel:"labels.success",successMessage:"labels.success.save-desc"}},layout:{extendExisting:!0,content:{width:"fixed",leftSpace:!0,rightSpace:!0}},initialize:function(){this.entityData=this.options.data(),this.$el.append(this.templates.skeleton({translations:this.translations})),this.startTasksComponents(),this.bindCustomEvents()},bindCustomEvents:function(){this.sandbox.on("husky.datagrid.tasks.number.selections",function(a){var b="husky.toolbar.content.item.enable";0===a&&(b="husky.toolbar.content.item.disable"),this.sandbox.emit(b,"deleteSelected",!1)}.bind(this)),this.sandbox.on("sulu.toolbar.delete",function(){this.sandbox.emit("husky.datagrid.tasks.items.get-selected",this.deleteTasksDialog.bind(this))}.bind(this))},startTasksComponents:function(){this.sandbox.start([{name:"list-toolbar@suluadmin",options:{el:this.$el.find("#tasks .task-list-toolbar"),hasSearch:!1,template:this.sandbox.sulu.buttons.get({add:{options:{callback:this.addTask.bind(this)}},deleteSelected:{}})}},{name:"datagrid@husky",options:{el:this.$el.find("#tasks .task-list"),url:b.getUrl(this.options.entityClass,this.entityData[this.options.idKey])+"&locale="+this.options.locale+"&sortBy=schedule&sortOrder=asc&schedule=future",resultKey:"tasks",instanceName:"tasks",actionCallback:this.editTask.bind(this),matchings:e}},{name:"datagrid@husky",options:{el:this.$el.find("#task-history .task-list"),url:b.getUrl(this.options.entityClass,this.entityData[this.options.idKey])+"&locale="+this.options.locale+"&sortBy=schedule&sortOrder=desc&schedule=past",resultKey:"tasks",instanceName:"task-history",viewOptions:{table:{selectItem:!1,cssClass:"light"}},contentFilters:{status:function(a){var b="fa-question";switch(a){case"completed":b="fa-check-circle";break;case"failed":b="fa-ban"}return'<span class="'+b+' task-state"/>'}},matchings:f}}])},editTask:function(a){var b=$("<div/>");this.$el.append(b),this.sandbox.start([{name:"automation-tab/overlay@suluautomation",options:{el:b,entityClass:this.options.entityClass,saveCallback:this.saveTask.bind(this),id:a}}])},addTask:function(){var a=$("<div/>");this.$el.append(a),this.sandbox.start([{name:"automation-tab/overlay@suluautomation",options:{el:a,entityClass:this.options.entityClass,saveCallback:this.saveTask.bind(this)}}])},deleteTasksDialog:function(a){this.sandbox.sulu.showDeleteDialog(function(b){b&&this.deleteTasks(a)}.bind(this))},deleteTasks:function(c){b.deleteItems(c).then(function(){a.each(c,function(a){this.sandbox.emit("husky.datagrid.tasks.record.remove",a)}.bind(this))}.bind(this))},saveTask:function(a){return a.locale=this.options.locale,a.entityClass=this.options.entityClass,a.entityId=this.entityData[this.options.idKey],b.save(a).then(function(b){var c="husky.datagrid.tasks.record.add";a.id&&(c="husky.datagrid.tasks.records.change"),this.sandbox.emit(c,b),this.sandbox.emit("sulu.labels.success.show",this.translations.successMessage,this.translations.successLabel)}.bind(this))}}});