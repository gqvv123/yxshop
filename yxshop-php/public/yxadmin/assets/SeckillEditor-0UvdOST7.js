/*
 * 兼容重建：装修编辑器「限时秒杀」属性编辑组件。
 * 字段依据主入口 i18n 中保留的 prop_session_id / prop_max_items 定义。
 * 导出形状与 CouponEditor-BIw_QKid.js 一致。
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

const defaults=()=>({session_id:"",max_items:4});

const SeckillEditor={
  __name:"SeckillEditor",
  props:{props:{type:Object,default:()=>({})}},
  emits:["update:props"],
  setup(props,{emit}){
    let t=()=>"";
    try{t=useI18n().t||t;}catch(e){}
    const L=makeL(t);
    const initial=props.props||{};
    const form=ref({...defaults(),...initial});
    const sessionId=ref(String(initial.session_id||""));
    const maxItems=ref(String(initial.max_items||4));
    watch(()=>props.props,p=>{
      const v=p||{};
      form.value={...defaults(),...v};
      sessionId.value=String(v.session_id||"");
      maxItems.value=String(v.max_items||4);
    },{deep:!0});
    const commit=()=>{
      form.value.session_id=sessionId.value.trim();
      form.value.max_items=Number(maxItems.value)||4;
      emit("update:props",{...form.value});
    };
    return()=>{
      const Input=resolveComponent("a-input"),Item=resolveComponent("a-form-item"),Form=resolveComponent("a-form");
      return(openBlock(),createBlock(Form,{model:form.value,layout:"vertical",size:"small"},{
        default:withCtx(()=>[
          createVNode(Item,{label:L("prop_session_id","场次ID")},{
            default:withCtx(()=>[createVNode(Input,{
              modelValue:sessionId.value,
              "onUpdate:modelValue":v=>sessionId.value=v,
              placeholder:L("placeholder_session_name","如：10:00 场"),
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
export{SeckillEditor as default};
