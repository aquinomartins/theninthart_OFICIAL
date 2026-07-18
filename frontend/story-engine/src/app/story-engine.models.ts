export type EngineStatus='idle'|'loading'|'ready'|'error';
export interface ManifestReference{path:string;count:number} export interface StoryManifestIndex{schemaVersion:string;manifestVersion:string;locale:string;manifests:Record<'controls'|'widgets'|'versions'|'quadrants'|'slots',ManifestReference>}
export interface StoryControl{id:string;position:number;defaultValue:boolean;enabled:boolean}
export interface StoryWidget{id:string;position:number;parameters:readonly {id:string;defaultValue:string|number|boolean}[]}
export interface StoryVersion{id:`v0${1|2|3|4|5|6|7}`;versionNumber:number;position:number;shortTitle:string;title:string;enabled:boolean}
export interface Quadrant{id:string;number:number;position:number;blockId:string;fixedFunction:string;narrativePurpose:string;aspectClass:string;allowedVersionIds:string[];enabled:boolean}
export interface QuadrantSlot{id:string;slotId:string;quadrantId:string;quadrantNumber:number;versionId:string;versionNumber:number;position:number;asset:{path:string|null;expectedPath:string;mimeType:string|null};status:string;altText:string;caption:string|null;dimensions:{width:number|null;height:number|null;aspectRatio:string|null}}
export interface StoryInputState{controls:Record<string,boolean>;widgets:Record<string,Record<string,string|number|boolean>>;visualVersionId:string;publicState:null|unknown}
export interface ResolvedStoryState{activeVersionId:string;activeSelections:Record<string,string>}
export interface ActivePanelSelection{quadrantId:string;versionId:string;slotId:string}
export interface StoryEngineError{code:string;phase:string;resource?:string;recoverable:boolean;message?:string;status?:number;timestamp:number}
export interface StoryEngineState{status:EngineStatus;activeVersionId:string;manifests:{controls:StoryControl[];widgets:StoryWidget[];versions:StoryVersion[];quadrants:Quadrant[];slots:QuadrantSlot[]};activeSelections:Record<string,string>;errors:StoryEngineError[];initializedAt:number|null;revision:number}
export interface BootstrapManifests{controls:StoryControl[];widgets:StoryWidget[];versions:StoryVersion[];quadrants:Quadrant[];slots:QuadrantSlot[]}
export interface StoryInputAdapter{getInitialState(manifests:BootstrapManifests,defaultVersionId:string):StoryInputState}
export class DefaultStoryInputAdapter implements StoryInputAdapter{getInitialState(m:BootstrapManifests,d:string):StoryInputState{const controls=Object.fromEntries(m.controls.map(c=>[c.id,false]));const widgets=Object.fromEntries(m.widgets.map(w=>[w.id,Object.fromEntries(w.parameters.map(p=>[p.id,p.defaultValue]))]));return{controls,widgets,visualVersionId:d,publicState:null}}}
export interface RealtimeTransport{readonly kind:'noop';connect():void;disconnect():void} export class NoopRealtimeTransport implements RealtimeTransport{readonly kind='noop' as const;connect(){} disconnect(){}}
