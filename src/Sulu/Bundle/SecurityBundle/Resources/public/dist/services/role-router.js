define(["services/husky/mediator"],function(a){"use strict";function b(){}return b.prototype={toEdit:function(b){a.emit("sulu.router.navigate","settings/roles/edit:"+b+"/details")},toAdd:function(){a.emit("sulu.router.navigate","settings/roles/new")},toList:function(){a.emit("sulu.router.navigate","settings/roles")}},new b});