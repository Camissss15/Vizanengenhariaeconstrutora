const pages=["inicio","conteudo","orcamentos","usuarios","perfil"];
const titles={inicio:"Visão geral",conteudo:"Conteúdo do site",orcamentos:"Solicitações de orçamento",usuarios:"Usuários e acessos",perfil:"Meu perfil"};
const $=id=>document.getElementById(id);
async function api(url,options={}) {
  const r=await fetch("api.php?acao="+url,options);
  const data=await r.json();
  if(!r.ok) throw new Error(data.erro||"Ocorreu um erro.");
  return data;
}
function jsonOptions(data){return {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(data)}}
function page(p){pages.forEach(x=>$("page-"+x).classList.toggle("active",x===p));document.querySelectorAll(".nav-link").forEach(b=>b.classList.toggle("active",b.dataset.page===p));$("pageTitle").textContent=titles[p];$("sidebar").classList.remove("open")}
document.querySelectorAll(".nav-link").forEach(b=>b.onclick=()=>page(b.dataset.page));
document.querySelectorAll("[data-go]").forEach(b=>b.onclick=()=>page(b.dataset.go));
$("mobileMenu").onclick=()=>$("sidebar").classList.toggle("open");
function status(s){return `<span class="status ${s==="Respondido"?"respondido":s==="Em análise"?"analise":""}">${s}</span>`}
async function carregarResumo(){
 const d=await api("resumo");$("statTotal").textContent=d.total;$("statPending").textContent=d.pendentes;$("statImages").textContent=d.imagens;$("statUsers").textContent=d.usuarios;
 $("recentQuotes").innerHTML=d.recentes.map(q=>`<tr><td>${esc(q.nome)}</td><td>${esc(q.assunto)}</td><td>${q.data_envio}</td><td>${status(q.status)}</td></tr>`).join("")||"<tr><td colspan='4'>Nenhuma solicitação recebida.</td></tr>";
}
async function carregarOrcamentos(){
 const all=await api("orcamentos");const termo=$("quoteSearch").value.toLowerCase(),f=$("quoteFilter").value;
 const arr=all.filter(q=>(!f||q.status===f)&&[q.nome,q.email,q.assunto].join(" ").toLowerCase().includes(termo));
 $("quotesTable").innerHTML=arr.map(q=>`<tr><td><b>${esc(q.nome)}</b><br>${esc(q.email)}<br>${esc(q.telefone||"")}</td><td>${esc(q.assunto)}<br>${esc(q.mensagem)}</td><td>${q.data_envio}</td><td>${status(q.status)}</td><td><select data-status="${q.id}"><option ${q.status==="Pendente"?"selected":""}>Pendente</option><option ${q.status==="Em análise"?"selected":""}>Em análise</option><option ${q.status==="Respondido"?"selected":""}>Respondido</option></select></td></tr>`).join("")||"<tr><td colspan='5'>Nenhuma solicitação encontrada.</td></tr>";
 document.querySelectorAll("[data-status]").forEach(s=>s.onchange=async()=>{try{await api("orcamento_status",jsonOptions({id:s.dataset.status,status:s.value}));await carregarOrcamentos();await carregarResumo()}catch(e){alert(e.message)}});
}
async function carregarConteudo(){
 const d=await api("conteudo");const sel=$("sectionSelect");sel.innerHTML="";
 ["Quem Somos","Serviços","Projetos","Onde Estamos"].forEach(x=>sel.add(new Option(x,x)));
 if(d.length){const atual=d.find(x=>x.secao===sel.value)||d[0];sel.value=atual.secao;$("descriptionText").value=atual.descricao}
 sel.onchange=()=>{$("descriptionText").value=(d.find(x=>x.secao===sel.value)||{}).descricao||""};
 const imgs=await api("imagens");$("imageList").innerHTML=imgs.map(i=>`<div class="image-thumb"><img src="${esc(i.caminho)}" alt="${esc(i.descricao)}"><p>${esc(i.descricao)}<br><button class="small-btn delete-btn" data-imgdel="${i.id}">Excluir</button></p></div>`).join("")||"<p class='muted'>Nenhuma imagem cadastrada.</p>";
 document.querySelectorAll("[data-imgdel]").forEach(b=>b.onclick=async()=>{if(confirm("Excluir esta imagem?")){try{await api("imagem_excluir",jsonOptions({id:b.dataset.imgdel}));carregarConteudo();carregarResumo()}catch(e){alert(e.message)}}});
}
$("descriptionForm").onsubmit=async e=>{e.preventDefault();try{const d=await api("conteudo",jsonOptions({secao:$("sectionSelect").value,descricao:$("descriptionText").value}));$("descriptionFeedback").textContent=d.mensagem}catch(e){$("descriptionFeedback").textContent=e.message}};
$("imageForm").onsubmit=async e=>{e.preventDefault();const fd=new FormData();fd.append("imagem",$("imageFile").files[0]);fd.append("descricao",$("imageAlt").value);try{const r=await fetch("api.php?acao=imagem_upload",{method:"POST",body:fd});const d=await r.json();if(!r.ok)throw new Error(d.erro);$("imageFeedback").textContent=d.mensagem;e.target.reset();carregarConteudo();carregarResumo()}catch(err){$("imageFeedback").textContent=err.message}};
async function carregarUsuarios(){
 const us=await api("usuarios");$("usersTable").innerHTML=us.map(u=>`<tr><td>${esc(u.nomeusu)}</td><td>${esc(u.loginusu)}</td><td>${esc(u.tipousu)}</td><td><button class="small-btn delete-btn" data-userdel="${u.Idu}">Excluir</button></td></tr>`).join("");
 document.querySelectorAll("[data-userdel]").forEach(b=>b.onclick=async()=>{if(confirm("Deseja excluir este usuário?")){try{await api("usuario_excluir",jsonOptions({id:b.dataset.userdel}));carregarUsuarios();carregarResumo()}catch(e){alert(e.message)}}});
}
$("userForm").onsubmit=async e=>{e.preventDefault();try{const d=await api("usuario_criar",jsonOptions({nome:$("newUserName").value,login:$("newUserLogin").value,email:$("newUserEmail").value,senha:$("newUserPassword").value,tipo:$("newUserRole").value}));$("userFeedback").textContent=d.mensagem;e.target.reset();carregarUsuarios();carregarResumo()}catch(err){$("userFeedback").textContent=err.message}};
$("profileForm").onsubmit=async e=>{e.preventDefault();try{const d=await api("perfil",jsonOptions({nome:$("profileName").value,email:$("profileEmail").value}));$("profileFeedback").textContent=d.mensagem;location.reload()}catch(err){$("profileFeedback").textContent=err.message}};
$("quoteSearch").oninput=carregarOrcamentos;$("quoteFilter").onchange=carregarOrcamentos;
function esc(v){return String(v??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]))}
(async()=>{try{await carregarResumo();await carregarOrcamentos();await carregarConteudo();try{await carregarUsuarios()}catch(e){console.warn("Listagem de usuários disponível apenas para administradores:",e.message)}}catch(e){alert(e.message)}})();
