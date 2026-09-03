import{h as n}from"./app-BzaJaxPt.js";const s={submit(r,t){return n.post(`/subscriptions/${r}/owner-rating`,t)},listForOwner(r,t={}){return n.get(`/owners/${r}/ratings`,{params:t})}};export{s as o};
