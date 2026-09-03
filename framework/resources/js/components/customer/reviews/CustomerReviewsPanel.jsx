import React, { useEffect, useState } from "react";
import "../../../../css/customer/reviews.css";

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
async function api(url, options={}) {
  const method=options.method||"GET";
  const response=await fetch(url,{method,credentials:"same-origin",headers:{Accept:"application/json",...(method!=="GET"?{"Content-Type":"application/json","X-CSRF-TOKEN":csrf()}: {})},body:options.body===undefined?undefined:JSON.stringify(options.body)});
  const data=await response.json().catch(()=>({}));
  if(!response.ok){const v=Object.values(data?.errors||{}).flat().find(Boolean);throw new Error(v||data?.message||"Review request failed.");}
  return data;
}
const Stars=({value})=><span className="customer-review-stars">{[1,2,3,4,5].map(n=><span key={n} className={n<=Number(value)?"is-filled":""}>{"\u2605"}</span>)}</span>;

export default function CustomerReviewsPanel({previewMode=false}) {
  const [loading,setLoading]=useState(true),[error,setError]=useState(""),[notice,setNotice]=useState("");
  const [eligible,setEligible]=useState([]),[mine,setMine]=useState([]),[publicReviews,setPublicReviews]=useState([]);
  const [selected,setSelected]=useState(null),[rating,setRating]=useState(5),[title,setTitle]=useState(""),[comment,setComment]=useState(""),[busy,setBusy]=useState(false);
  const load=async()=>{setLoading(true);setError("");try{if(previewMode){const r=await api("/api/store/reviews");setPublicReviews(r.reviews||[]);}else{const [mineResult,publicResult]=await Promise.all([api("/api/user/reviews"),api("/api/store/reviews")]);setEligible(mineResult.eligible_products||[]);setMine(mineResult.my_reviews||[]);setPublicReviews(publicResult.reviews||[]);}}catch(e){setError(e.message);}finally{setLoading(false);}};
  useEffect(()=>{load();},[previewMode]);
  const submit=async(e)=>{e.preventDefault();if(!selected||busy)return;setBusy(true);setError("");try{const r=await api("/api/user/reviews",{method:"POST",body:{product_id:selected.product_id,rating,title:title.trim()||null,comment:comment.trim()}});setNotice(r.message);setSelected(null);setTitle("");setComment("");setRating(5);await load();}catch(err){setError(err.message);}finally{setBusy(false);}};
  if(loading)return <section className="customer-page-section"><div className="customer-review-empty">Loading reviews...</div></section>;
  const card=(r)=><article className="customer-review-card" key={r.review_id}><div className="customer-review-head"><div><strong>{r.product_name}</strong><small>{r.customer_name||r.sku}</small></div><Stars value={r.rating}/></div>{r.title&&<h3>{r.title}</h3>}<p>{r.comment}</p><span className="customer-review-verified">Verified Purchase</span>{r.status&&<small className="customer-review-status">{r.status}</small>}</article>;
  return <section className="customer-page-section customer-reviews-page"><div className="customer-page-title"><div><span className="customer-kicker">VERIFIED PURCHASE REVIEWS</span><h1>Product reviews</h1><p>{previewMode?"Preview public customer reviews without exposing private purchase history.":"Only fulfilled purchases can be reviewed. One review per product."}</p></div></div>
    {notice&&<div className="customer-review-notice">{notice}</div>}{error&&<div className="customer-review-error">{error}</div>}
    {previewMode?<div className="customer-review-grid">{publicReviews.length?publicReviews.map(card):<div className="customer-review-empty">No visible product reviews yet.</div>}</div>:<>
      <div className="customer-review-section"><h2>Ready to review</h2>{eligible.length?<div className="customer-review-grid">{eligible.map(p=><article className="customer-review-eligible" key={p.product_id}><div><small>{p.sku}</small><strong>{p.name}</strong><span>Order #{p.order_id}</span></div><button type="button" onClick={()=>setSelected(p)}>Write Review</button></article>)}</div>:<div className="customer-review-empty">No fulfilled, unreviewed products are waiting for feedback.</div>}</div>
      {selected&&<form className="customer-review-form" onSubmit={submit}><div className="customer-review-form-head"><h2>Review {selected.name}</h2><button type="button" onClick={()=>setSelected(null)}>Cancel</button></div><label>Rating<div className="customer-review-rating">{[1,2,3,4,5].map(n=><button type="button" key={n} className={n<=rating?"is-selected":""} onClick={()=>setRating(n)}>{"\u2605"}</button>)}</div></label><label>Title (optional)<input maxLength={120} value={title} onChange={e=>setTitle(e.target.value)}/></label><label>Comment<textarea required minLength={3} maxLength={2000} rows={5} value={comment} onChange={e=>setComment(e.target.value)}/></label><button className="customer-review-submit" disabled={busy}>{busy?"Submitting...":"Submit Review"}</button></form>}
      <div className="customer-review-section"><h2>My reviews</h2><div className="customer-review-grid">{mine.length?mine.map(card):<div className="customer-review-empty">You have not submitted a product review yet.</div>}</div></div>
      <div className="customer-review-section"><h2>Customer reviews</h2><p className="customer-review-section-copy">Visible verified-purchase reviews from Walang Brownout customers.</p><div className="customer-review-grid">{publicReviews.length?publicReviews.map(card):<div className="customer-review-empty">No visible product reviews yet.</div>}</div></div>
    </>}
  </section>;
}