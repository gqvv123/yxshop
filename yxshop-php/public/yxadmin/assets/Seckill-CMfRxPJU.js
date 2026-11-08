/*
 * 兼容重建：装修编辑器「限时秒杀」预览组件。
 * 原构建产物缺少本 chunk，导出形状与 Coupon-DPp8Z5Hi.js 一致。
 * 不反向 import 主入口，避免新增循环依赖。
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

const o={class:"yx-mp-sk"},a={class:"yx-mp-sk__hd"},s={class:"yx-mp-sk__title"},
      q={class:"yx-mp-sk__timer"},c={class:"yx-mp-sk__list"},d={class:"yx-mp-sk__card"},
      f={class:"yx-mp-sk__thumb"},g={class:"yx-mp-sk__info"},p={class:"yx-mp-sk__name"},
      r={class:"yx-mp-sk__price"},b={class:"yx-mp-sk__old"},n={class:"yx-mp-sk__btn"};

const Seckill={
  __name:"Seckill",
  props:{props:{type:Object,default:()=>({})}},
  setup(){
    let t=()=>"";
    try{t=useI18n().t||t;}catch(e){}
    const L=makeL(t);
    const card=(name,price,old)=>h("div",d,[
      h("div",f,null),
      h("div",g,[
        h("div",p,toDisplayString(name),1),
        h("div",r,toDisplayString(price),1),
        h("div",b,toDisplayString(old),1),
        h("span",n,"马上抢")
      ])
    ]);
    const title=L("seckill_title","限时秒杀");
    return()=>(openBlock(),createElementBlock("div",o,[
      h("div",a,[
        h("span",s,toDisplayString(title),1),
        h("span",q,"02 : 15 : 38")
      ]),
      h("div",c,[
        card(title+" · 商品 1","¥39.00","¥99.00"),
        card(title+" · 商品 2","¥19.90","¥59.00")
      ])
    ]));
  }
};
export{Seckill as default};
