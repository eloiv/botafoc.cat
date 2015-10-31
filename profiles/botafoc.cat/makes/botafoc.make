; Configuration: Enable and disable botafoc makes.
; ----------------
; Make API version
; ----------------
api = 2

; Core version
; ------------
core = 8.x

; Core project
; ------------
projects[] = drupal

;The most contrib modules used
;-----------------------------
includes[botafoc] = "./botafoc_base.make"

;Construct and Develop modules
;-----------------------------
includes[] = "./botafoc_devel.make"
