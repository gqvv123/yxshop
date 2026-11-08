/*
 * 兼容重建：社区/养老模块 API 层 (community-DkbL50GJ.js)
 *
 * 背景：原 dist 缺少本 chunk，但 11 个页面 chunk 通过静态 import 依赖它的 38 个具名导出，
 *       缺失时这些页面会在模块实例化阶段直接报错（白屏），而不只是接口 404。
 *       开源版后端 config/route/admin_api.php 中也没有 community 相关路由。
 *
 * 策略：导出名/调用签名与调用方完全对齐，请求统一走主入口的 http 实例；
 *       - 后端存在时：与原本的 API 封装行为一致，正常读写；
 *       - 后端缺失(404/405/501)时：读操作返回结构兼容的空数据（页面正常渲染空列表），
 *         写操作抛出的明确错误（页面 catch 显示"操作失败"，绝不假装成功）。
 *
 * 接入商业版后端时：只需按上游实际路由调整下方 BASE 与各函数路径即可，无需改动调用方。
 * 读/写分类依据：对全部 11 个引用页面中 `await X(...)` 的调用上下文逐一核对。
 */
import{h as http}from"./index-BMko6wC9.js";

const BASE="/admin/api/community";
const NOT_AVAILABLE="社区/养老模块需要商业版授权（route_module_map 门禁返回 403）；开源版兼容层已就绪，放行授权后即可使用";

// 后端对未注册路由返回的是 HTTP 200 + {code:404,message:"接口不存在"}，
// 主入口拦截器会把它转成 Error("接口不存在")（没有 response 对象），
// 因此不能只判断 err.response.status，必须同时识别业务码与错误文案。
function isMissing(err){
  const r=err&&err.response,status=r&&r.status;
  if([404,405,501].includes(status)) return true;
  const body=r&&r.data;
  if(body&&[404,403].includes(Number(body.code))) return true;
  // 商业版授权门禁返回 code=403 + "此功能需要商业版授权。"
  return /接口不存在|资源不存在|路由不存在|此功能需要.*授权|商业版授权|Not Found/i.test(String((err&&err.message)||""));
}

function guard(promise,readable){
  return promise.catch(err=>{
    if(!isMissing(err)) return Promise.reject(err);
    if(readable) return {code:0,message:"",data:{list:[],total:0,data:[],count:0},__ossStub:true};
    return Promise.reject(new Error(NOT_AVAILABLE));
  });
}

const R=path=>params=>guard(http({url:BASE+path,method:"get",params}),true);
const W=(path,method)=>(...args)=>{
  const first=args[0],second=args[1];
  const scalar=typeof first==="string"||typeof first==="number";
  // 单参数标量 + DELETE/PUT 是 /path/:id；其余为请求体
  const hasId=scalar&&(args.length>1||method==="delete"||method==="put");
  const url=BASE+path+(hasId?"/"+encodeURIComponent(first):"");
  const data=hasId?(args.length>1?second:void 0):first;
  return guard(http({url,method:method||"post",data}),false);
};

/* ---- 读：社区看板 / 订单 / 食堂 / 服务 / 人员 / 评价 / 老人 / 区域 / 绑定 ---- */
const a=R("/care/orders");            // 护理订单列表 (care-orders)
const b=R("/care/providers");         // 服务人员列表 (care-providers / 分配弹窗)
const B=R("/elders/binds");           // 老人绑定列表
const F=R("/orders");                 // 社区订单列表
const g=R("/canteens");               // 社区食堂列表
const I=R("/regions");                // 区域列表
const k=R("/care/reviews");           // 服务评价列表
const l=R("/care/reviews/stats");     // 评价统计
const n=R("/care/services");          // 服务项目列表
const s=R("/dashboard");              // 社区看板
const w=R("/elders");                 // 老人档案列表

/* ---- 写：新增 / 更新 / 删除 / 审核 / 状态 / 批量操作 ---- */
const A=W("/elders","post");
const C=W("/elders/binds","delete");
const D=W("/elders/binds","put");
const E=W("/elders/binds","post");
const G=W("/orders/status","post");
const H=W("/orders/cancel","post");
const J=W("/regions","delete");
const K=W("/regions","put");
const L=W("/regions","post");
const c=W("/canteens","post");
const d=W("/canteens","delete");
const e=W("/care/orders/status","post");
const f=W("/care/orders/assign","post");
const h=W("/care/providers","delete");
const i=W("/care/providers","put");
const j=W("/care/providers","post");
const m=W("/care/reviews","delete");
const o=W("/care/services","delete");
const p=W("/care/services","put");
const q=W("/care/services","post");
const r=W("/care/reviews/reply","post");
const t=W("/care/reviews/toggle","post");
const u=W("/canteens","put");
const v=W("/care/providers/verify","post");
const x=W("/elders","delete");
const y=W("/elders/batch-delete","post");
const z=W("/elders","put");

export{A,B,C,D,E,F,G,H,I,J,K,L,a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z};
