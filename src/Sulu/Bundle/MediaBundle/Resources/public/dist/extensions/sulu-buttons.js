!function(){"use strict";define(function(){var a=function(a){var b=a.sandbox.sulu.buttons.getApiButton("layout");b.dropdownItems={masonry:{},table:{}};var c=a.sandbox.sulu.buttons.getApiButton("permission");return c.title="security.roles.permissions",c.icon="lock",c.callback=function(){a.sandbox.emit("sulu.toolbar.collection-permissions")},[{name:"mediaDecoratorDropdown",template:b},{name:"permissionSettings",template:c}]},b=function(a){return[{name:"masonry",template:{title:"sulu.toolbar.masonry",callback:function(){a.sandbox.emit("sulu.toolbar.change.masonry")}}},{name:"editCollection",template:{title:"public.edit",callback:function(){a.sandbox.emit("sulu.toolbar.edit-collection")}}},{name:"moveCollection",template:{title:"sulu.collection.move",callback:function(){a.sandbox.emit("sulu.toolbar.move-collection")}}},{name:"deleteCollection",template:{title:"public.delete",callback:function(){a.sandbox.emit("sulu.toolbar.delete-collection")}}}]};return{initialize:function(c){c.sandbox.sulu.buttons.push(a(c)),c.sandbox.sulu.buttons.dropdownItems.push(b(c))}}})}();