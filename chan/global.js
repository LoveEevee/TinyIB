'use strict';

function toreltime(inti,tnow){
	var timd=tnow-inti
	var sec=timd%60|0
	timd/=60
	var min=timd%60|0
	timd/=60
	var hrs=timd%24|0
	timd/=24
	var day=timd%30.4375|0
	var week=timd/7|0
	var dayw=day%7|0
	timd/=30.4375
	var mon=timd%12|0
	var yer=Math.floor(timd/12)
	if(!mon&&!yer&&day>6){
		var tout=week+' week'+(week>1?'s ':' ')+(dayw&&week<2?'and '+dayw+' day'+(dayw>1?'s ':' '):'')
	}else{
		var till=[
			yer?yer+' year'+(yer>1?'s ':' '):'',
			mon&&yer<2?mon+' month'+(mon>1?'s ':' '):'',
			day&&mon<2?day+' day'+(day>1?'s ':' '):'',
			hrs&&day<2?hrs+' hour'+(hrs>1?'s ':' '):'',
			min&&hrs<3?min+' minute'+(min>1?'s ':' '):'',
			(sec&&min<10)||!min?sec+' second'+(sec==1?' ':'s '):''
		]
		for(var g=0;g<till.length&&!till[g];g++){}
		var tout=till[g]+(till[g+1]?'and '+till[g+1]:'')
	}
	var newdt=new Date(inti*1000)
	return [tout+'ago',newdt.toDateString()+' '+newdt.toLocaleTimeString()]
}

function updatetimes(){
	var timestamp=document.getElementsByClassName('timestamp')
	var tnow=new Date().getTime()/1000|0
	for(var i=0;i<timestamp.length;i++){
		var outp=toreltime(timestamp[i].getAttribute('numbers'),tnow)
		timestamp[i].innerHTML=outp[0]
		timestamp[i].title=outp[1]
	}
}
updatetimes()
setInterval(updatetimes,30000)

var pstf=document.postform.message
function quote(num){
	var slct=getSelection()||document.selection.type&&document.selection.createRange().text||''
	var num=num?'>>'+num+'\n':''
	if(slct+''){
		num+=('>'+slct).trim().replace(/[\r\n]+/g,'\n>')+'\n'
	}
	pstf.value=pstf.value?pstf.value.slice(0,pstf.selectionStart)+num+pstf.value.slice(pstf.selectionEnd):num
	pstf.selectionStart=pstf.selectionEnd=pstf.selectionStart+num.length
	pstf.selectionStart==pstf.value.length&&(pstf.scrollTop=pstf.scrollHeight)
	pstf.focus()
}

var imxp,imxd
function expandthumbs(){
	imxp=document.createElement('div')
	imxp.id='imgexpand'
	imxp.style.display='none'
	imxp.addEventListener('click',function(){
		expandthumb()
	})
	imxd=document.createElement('img')
	imxp.appendChild(imxd)
	document.body.appendChild(imxp)
	var thmb=document.getElementsByClassName('thumb')
	for(var i=0;i<thmb.length;i++){
		thmb[i].addEventListener('click',expandthumb)
	}
}

expandthumbs()
function expandthumb(ev){
	if(ev){
		if(/\.(png|jpe?g|gif)$/.test(ev.target.parentNode.href)){
			ev.preventDefault()
			imxp.style.display='block'
			imxd.src=ev.target.parentNode.href
		}
	}else{
		imxp.style.display='none'
		imxd.src=''
	}
}

var lastop=0
function givebacklinks(){
	var quotes=document.querySelectorAll('a,.thread')
	for(var i=0;i<quotes.length;i++){
		if(quotes[i].tagName=='A'){
			if(quotes[i].innerHTML.slice(0,8)=='&gt;&gt;'){
				var num=quotes[i].innerHTML.slice(8)
				quotes[i].dataset.pid=num
				quotes[i].onmouseover=quotehover
				quotes[i].onmouseout=function(event){
					quotehover(event,1)
				}
				if(num==lastop){
					quotes[i].innerHTML+=' (OP)'
				}else{
					var cross=quotes[i].href.match(/(?:^|..\/)thread\/(\d+)#(\d+)$/)
					if(cross&&cross[1]!=lastop){
						if(cross[1]==cross[2]){
							quotes[i].innerHTML+=' (OP)'
						}
						quotes[i].innerHTML+=' &rarr;'
					}
				}
				makebacklink(num,quotes[i].parentNode.parentNode.id)
			}
		}else{
			lastop=quotes[i].id
		}
	}
}
givebacklinks()

function makebacklink(num,text){
	var target=document.getElementById(num)
	if(target){
		var postinfo=target.getElementsByClassName('postInfo')[0]
		if(!postinfo.querySelector('[href="#'+text+'"]')){
			var backlink=document.createElement('a')
			backlink.innerHTML='&gt;&gt;'+text
			backlink.href='#'+text
			backlink.classList.add('backlink')
			backlink.dataset.pid=text
			backlink.onmouseover=quotehover
			backlink.onmouseout=function(event){
				quotehover(event,1)
			}
			var replylink=postinfo.getElementsByClassName('replylink')[0]
			if(replylink){
				postinfo.insertBefore(backlink,replylink)
			}else{
				postinfo.appendChild(backlink)
			}
		}
	}
}

var hoveredpost=null
function quotehover(event,hoverout){
	var num=event.target.dataset.pid
	var target=document.getElementById(num)
	if(target){
		if(hoverout){
			target.classList.remove('hovered')
			if(hoveredpost){
				document.body.removeChild(hoveredpost)
				hoveredpost=null
			}
			var backbacklinks=document.querySelectorAll('.backbacklink')
			for(var i=0;i<backbacklinks.length;i++){
				backbacklinks[i].classList.remove('backbacklink')
			}
		}else{
			var frompost=event.target.parentNode.parentNode.id
			var backbacklinks=target.querySelectorAll(':scope>.message [data-pid="'+frompost+'"]')
			for(var i=0;i<backbacklinks.length;i++){
				backbacklinks[i].classList.add('backbacklink')
			}
			var op=target.classList.contains('thread')
			if(op){
				var thetarget=target.querySelector(':scope>.message')
			}else{
				var thetarget=target
			}
			if(thetarget.offsetTop<scrollY||innerHeight+scrollY<thetarget.offsetTop+thetarget.offsetHeight){
				hoveredpost=document.createElement('div')
				hoveredpost.classList.add('hoveredpost')
				hoveredpost.style.left=event.target.offsetLeft+'px'
				hoveredpost.style.top=(event.target.offsetTop+event.target.offsetHeight)+'px'
				if(op){
					var cloned=target.querySelectorAll(':scope>.filesize,:scope>.postInfo,:scope>.message')
					for(var i=0;i<cloned.length;i++){
						hoveredpost.appendChild(cloned[i].cloneNode(true))
					}
				}else{
					hoveredpost.appendChild(target.cloneNode(true))
				}
				document.body.appendChild(hoveredpost)
			}else{
				target.classList.add('hovered')
			}
		}
	}
}
var password=''
document.cookie.split(';').some(function(a){
	var b=a.split('=')
	if(b[0]=='password'){
		password=b[1]
		return 1
	}
})
if(!password||!(password*0+1)){
	password=Math.floor(Math.random()*1E16)
	document.cookie='password='+password+';expires='+(new Date(Date.now()+3E10)).toUTCString()+';path=/'
}
var passes=document.getElementsByName('password')
for(var i=0;i<passes.length;i++){
	passes[i].setAttribute('value',password)
}