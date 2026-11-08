/*
 * 兼容重建：装修编辑器「拼团活动」属性编辑组件。
 * 导出形状与 CouponEditor-BIw_QKid.js / PointExchangeEditor-5q5VFhPu.js 一致：
 *   default = Vue 组件，props: { props: Object }，emits: ["update:props"]
 * 字段依据主入口 i18n 中保留的 prop_group_buy_ids / prop_max_items 定义。
 */
import{u as useI18n}from"./vendor-i18n-CADPzse4.js";
import{aU as ref,bE as watch,aZ as resolveComponent,aL as openBlock,s as createBlock,bJ as withCtx,I as createVNode,bk as translator}from"./vendor-misc-B4xDkSEx.js";
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

const defaults=()=>({group_buy_ids:[],max_items:4});

const GroupBuyEditor={
  __name:"GroupBuyEditor",
  props:{props:{type:Object,default:()=>({})}},
  emits:["update:props"],
  setup(props,{emit}){
    let t=()=>"";
    try{t=useI18n().t||t;}catch(e){}
    const L=makeL(t);
    const initial=props.props||{};
    const form=ref({...defaults(),...initial});
    const ids=ref((initial.group_buy_ids||[]).join(","));
    const maxItems=ref(String(initial.max_items||4));
    watch(()=>props.props,p=>{
      const v=p||{};
      form.value={...defaults(),...v};
      ids.value=(v.group_buy_ids||[]).join(",");
      maxItems.value=String(v.max_items||4);
    },{deep:!0});
    const commit=()=>{
      form.value.group_buy_ids=ids.value.split(",").map(x=>x.trim()).filter(Boolean);
      form.value.max_items=Number(maxItems.value)||4;
      emit("update:props",{...form.value});
    };
    return()=>{
      const Input=resolveComponent("a-input"),Item=resolveComponent("a-form-item"),Form=resolveComponent("a-form");
      return(openBlock(),createBlock(Form,{model:form.value,layout:"vertical",size:"small"},{
        default:withCtx(()=>[
          createVNode(Item,{label:L("prop_group_buy_ids","拼团活动ID")},{
            default:withCtx(()=>[createVNode(Input,{
              modelValue:ids.value,
              "onUpdate:modelValue":v=>ids.value=v,
              placeholder:L("group_buy_ids_placeholder","多个ID用逗号分隔"),
              onChange:commit
            },null,8,["modelValue","placeholder"])]),
            _:1
          },8,["label"]),
          createVNode(Item,{label:L("prop_max_items","最大展示数")},{
            default:withCtx(()=>[createVNode(Input,{
              modelValue:maxItems.value,
              "onUpdate:modelValue":v=>maxItems.value=v,
              placeholder:"4",
              onChange:commit
            },null,8,["modelValue"])]),
            _:1
          },8,["label"])
        ]),
        _:1
      },8,["model"]));
    };
  }
};
export{GroupBuyEditor as default};
