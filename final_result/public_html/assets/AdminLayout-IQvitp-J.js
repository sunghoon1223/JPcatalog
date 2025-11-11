import{j as e}from"./ui-vendor-D7YrltUJ.js";import{c as b,u as g,r as j}from"./react-vendor-XbLGo5ol.js";import{c as n,q as f,e as l,N,L as k,o as i,w}from"./index-iJ4ZFglL.js";import{M as v}from"./message-square-wSPTWZHd.js";import{U as L}from"./users-NOTXd2iJ.js";/**
 * @license lucide-react v0.462.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const M=n("Bell",[["path",{d:"M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9",key:"1qo2s2"}],["path",{d:"M10.3 21a1.94 1.94 0 0 0 3.4 0",key:"qgo35s"}]]);/**
 * @license lucide-react v0.462.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const C=n("LayoutDashboard",[["rect",{width:"7",height:"9",x:"3",y:"3",rx:"1",key:"10lvy0"}],["rect",{width:"7",height:"5",x:"14",y:"3",rx:"1",key:"16une8"}],["rect",{width:"7",height:"9",x:"14",y:"12",rx:"1",key:"1hutg5"}],["rect",{width:"7",height:"5",x:"3",y:"16",rx:"1",key:"ldoo1y"}]]);/**
 * @license lucide-react v0.462.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const z=n("LogOut",[["path",{d:"M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4",key:"1uf3rs"}],["polyline",{points:"16 17 21 12 16 7",key:"1gabdz"}],["line",{x1:"21",x2:"9",y1:"12",y2:"12",key:"1uyos4"}]]);/**
 * @license lucide-react v0.462.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const A=n("Package",[["path",{d:"M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z",key:"1a0edw"}],["path",{d:"M12 22V12",key:"d0xqtd"}],["path",{d:"m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7",key:"yx3hmr"}],["path",{d:"m7.5 4.27 9 5.15",key:"1c824w"}]]);/**
 * @license lucide-react v0.462.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const S=n("ShoppingCart",[["circle",{cx:"8",cy:"21",r:"1",key:"jimo8o"}],["circle",{cx:"19",cy:"21",r:"1",key:"13723u"}],["path",{d:"M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12",key:"9zh506"}]]);function I({children:d}){const{user:s,signOut:h,refreshing:m}=f(),c=b(),r=g(),[t,x]=j.useState(!0),p=async()=>{const{error:a}=await h();a||(w.success("로그아웃되었습니다."),c("/admin/login"))},u=[{icon:C,label:"대시보드",path:"/admin/dashboard"},{icon:A,label:"제품 관리",path:"/admin/products"},{icon:S,label:"주문 관리",path:"/admin/orders"},{icon:M,label:"공지사항",path:"/admin/notices"},{icon:v,label:"게시판",path:"/admin/boards"},{icon:L,label:"사용자",path:"/admin/users"}];return e.jsxs("div",{className:"min-h-screen bg-slate-50",children:[e.jsx("header",{className:"bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm",children:e.jsxs("div",{className:"flex items-center justify-between px-6 py-4",children:[e.jsxs("div",{className:"flex items-center gap-4",children:[e.jsx(l,{variant:"ghost",size:"icon",onClick:()=>x(!t),className:"lg:hidden",children:e.jsx(N,{className:"h-5 w-5"})}),e.jsx("h1",{className:"text-xl font-bold text-slate-900",children:"JP Caster 관리"})]}),e.jsxs("div",{className:"flex items-center gap-4",children:[e.jsxs("div",{className:"hidden sm:flex items-center gap-2 text-sm",children:[e.jsx("div",{className:"bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-medium",children:"관리자"}),e.jsx("span",{className:"text-slate-600",children:(s==null?void 0:s.full_name)||(s==null?void 0:s.email)})]}),e.jsxs(l,{variant:"ghost",size:"sm",onClick:p,children:[e.jsx(z,{className:"mr-2 h-4 w-4"}),e.jsx("span",{className:"hidden sm:inline",children:"로그아웃"})]})]})]})}),m&&e.jsxs("div",{className:"bg-blue-50 border-b border-blue-100 text-blue-800 text-sm px-6 py-2 flex items-center gap-2",children:[e.jsx(k,{className:"h-4 w-4 animate-spin"}),e.jsx("span",{children:"관리자 세션을 갱신하는 중입니다..."})]}),e.jsxs("div",{className:"flex",children:[e.jsx("aside",{className:i("bg-white border-r border-slate-200 min-h-[calc(100vh-73px)] p-4 transition-all duration-300",t?"w-64":"w-0 p-0 overflow-hidden lg:w-16"),children:e.jsx("nav",{className:"space-y-1",children:u.map(a=>{const y=a.icon,o=r.pathname===a.path||r.pathname.startsWith(a.path+"/");return e.jsxs(l,{variant:o?"secondary":"ghost",className:i("w-full justify-start",o&&"bg-blue-50 text-blue-600 hover:bg-blue-100",!t&&"lg:justify-center"),onClick:()=>c(a.path),children:[e.jsx(y,{className:i("h-4 w-4",t?"mr-2":"lg:mr-0")}),t&&e.jsx("span",{children:a.label})]},a.path)})})}),e.jsx("main",{className:"flex-1 p-6 lg:p-8",children:d})]})]})}export{I as A,A as P,S};
