/*
 * 兼容重建：装修编辑器「拼团活动」预览组件。
 * 原构建产物缺少本 chunk，但主入口的组件注册表与 __vite__mapDeps 仍引用它。
 * 导出形状与同目录 Coupon-DPp8Z5Hi.js / PointExchange-B2DMzUSJ.js 保持一致：
 *   default = Vue 组件，props: { props: Object }
 * 依赖符号沿用同类 chunk 的 import 别名，不反向 import 主入口，避免新增循环依赖。
 */
import{u as useI18n}from"./vendor-i18n-CADPzse4.js";
import{aL as openBlock,u as createElementBlock,v as h,bb as toDisplayString,bk as translator}from"./vendor-misc-B4xDkSEx.js";
import"./vendor-vue-BkO0pQ2u.js";

function makeL(t){
  let dict=null;
  try{dict=translator(t);}catch(e){}
  return (key,fallback)=>{
    const full="mini_page."+key;
    let v;
    try{v=dict?dict(full):void 0;}catch(e){}
    return v==null||v===""||v===full?fallback:v;
  };
}

const o={class:"yx-mp-gb"},a={class:"yx-mp-gb__hd"},s={class:"yx-mp-gb__title"},
      c={class:"yx-mp-gb__list"},d={class:"yx-mp-gb__card"},f={class:"yx-mp-gb__thumb"},
      g={class:"yx-mp-gb__info"},p={class:"yx-mp-gb__name"},r={class:"yx-mp-gb__price"},
      b={class:"yx-mp-gb__tag"},n={class:"yx-mp-gb__btn"};

const GroupBuy={
  __name:"GroupBuy",
  props:{props:{type:Object,default:()=>({})}},
  setup(){
    let t=()=>"";
    try{t=useI18n().t||t;}catch(e){}
    const L=makeL(t);
    const card=(name,price,people)=>h("div",d,[
      h("div",f,null),
      h("div",g,[
        h("div",p,toDisplayString(name),1),
        h("div",r,toDisplayString(price),1),
        h("div",b,toDisplayString(people),1),
        h("span",n,"去拼团")
      ])
    ]);
    const title=L("group_buy_title","拼团活动"),people=L("group_people_sample","2人成团");
    return()=>(openBlock(),createElementBlock("div",o,[
      h("div",a,[h("span",s,toDisplayString(title),1)]),
      h("div",c,[
        card(title+" · 商品 1","¥99.00",people),
        card(title+" · 商品 2","¥59.00",people)
      ])
    ]));
  }
};
export{GroupBuy as default};
