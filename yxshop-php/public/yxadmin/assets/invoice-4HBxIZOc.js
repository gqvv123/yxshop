/*
 * 兼容重建：发票模块 API 层 (invoice-4HBxIZOc.js)
 *
 * 背景：原 dist 缺少本 chunk；index-DBfWu4sI.js（发票管理）、index-BYVKEiEG.js（供应商发票）
 *       等 3 个页面 chunk 静态 import 它的 11 个具名导出。缺失会导致页面模块加载失败。
 *       开源版后端 config/route/admin_api.php 中没有 invoice 相关路由。
 *
 * 策略与 community-DkbL50GJ.js 一致：保留真实 HTTP 调用，后端缺失时读返回结构兼容空数据、
 * 写抛出明确错误。接入商业版后端时只需按实际路由调整下方路径表。
 */
import{h as http}from"./index-BMko6wC9.js";

const BASE="/admin/api";
const NOT_AVAILABLE="发票接口调用失败（开源版兼容层未启用或后端异常）";

// 同 community-DkbL50GJ.js：需同时识别 HTTP 404 与业务码 404（Webman 返回 HTTP 200）
function isMissing(err){
  const r=err&&err.response,status=r&&r.status;
  if([404,405,501].includes(status)) return true;
  const body=r&&r.data;
  if(body&&[404,403].includes(Number(body.code))) return true;
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
  const hasId=scalar&&(args.length>1||method==="delete"||method==="put");
  const url=BASE+path+(hasId?"/"+encodeURIComponent(first):"");
  const data=hasId?(args.length>1?second:void 0):first;
  return guard(http({url,method:method||"post",data}),false);
};

/* 读：发票抬头列表 / 开票记录列表（页面按 headers / records 切换） */
const g=R("/invoice/headers");
const a=R("/invoice/records");

/* 写：创建 / 更新 / 删除 / 批量删除 / 设为默认 */
const b=W("/invoice/records","delete");
const c=W("/invoice/headers/batch-delete","post");
const d=W("/invoice/headers","delete");
const e=W("/invoice/records/batch-delete","post");
const f=W("/invoice/headers","post");
const h=W("/invoice/headers","put");
const i=W("/invoice/records","post");
const s=W("/invoice/headers/default","post");
const u=W("/invoice/headers","put");

export{a,b,c,d,e,f,g,h,i,s,u};
