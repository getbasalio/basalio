"""Verify shipped small-text contrast and editor/frontend parity."""
from pathlib import Path
import re
root=Path(__file__).resolve().parents[1]
def luminance(hexcolor):
 values=[int(hexcolor[i:i+2],16)/255 for i in (1,3,5)]
 values=[x/12.92 if x<=0.04045 else ((x+0.055)/1.055)**2.4 for x in values]
 return sum(x*w for x,w in zip(values,[0.2126,0.7152,0.0722]))
def ratio(a,b):
 x,y=sorted([luminance(a),luminance(b)]);return (y+0.05)/(x+0.05)
assert ratio('#838383','#ffffff')<4.5
print('PASS known-bad #838383 on white is rejected (%.2f:1)'%ratio('#838383','#ffffff'))
tokens=[]
for file in ['basalio-core.css','basalio-editor.css']:
 css=(root/'assets/css'/file).read_text();roles=dict(re.findall(r'--basalio-([\w-]+):\s*(#[\da-fA-F]{6});',css));tokens.append(roles['text-muted'])
 for background in [roles['surface'],roles['surface-raised'],'#ffffff']:
  score=ratio(roles['text-muted'],background);assert score>=4.5,(file,background,score);print('PASS %s muted text on %s: %.2f:1'%(file,background,score))
assert tokens[0]==tokens[1]
print('PASS frontend and editor use the same muted-text default')
