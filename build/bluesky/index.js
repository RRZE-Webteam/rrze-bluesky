!function(){"use strict";var e=window.ReactJSXRuntime,t=window.wp.blocks,i=window.wp.blockEditor,r=JSON.parse('{"UU":"rrze-bluesky/bluesky"}');(0,t.registerBlockType)(r.UU,{icon:{src:(0,e.jsxs)("svg",{id:"Ebene_1",xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 512 512",children:[(0,e.jsx)("rect",{x:"60.05",y:"115.69",width:"112.94",height:"280.62",rx:"5.73",ry:"5.73",fill:"evenodd",strokeWidth:"0"}),(0,e.jsx)("rect",{x:"199.53",y:"115.69",width:"112.94",height:"280.62",rx:"5.73",ry:"5.73",fill:"evenodd",strokeWidth:"0"}),(0,e.jsx)("rect",{x:"339.01",y:"115.69",width:"112.94",height:"280.62",rx:"5.73",ry:"5.73",fill:"evenodd",strokeWidth:"0"})]})},__experimentalLabel:function(e,t){var i=t.context,r=e.title;if("list-view"===i&&r)return r},edit:function(){var t=(0,i.useBlockProps)();return(0,e.jsx)("div",Object.assign({},t,{children:(0,e.jsx)("h2",{children:"Hello World!"})}))},save:function(t){var r=t.attributes,s=i.useBlockProps.save();return(0,e.jsx)(e.Fragment,{children:(0,e.jsx)("div",Object.assign({},s,{children:(0,e.jsxs)("h2",{children:["Hello World! ",r.title]})}))})}})}();