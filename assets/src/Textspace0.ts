// <reference path="Textspace.ts"/>

module Textspace
{
    export var text: string = "";
    export var selection = {'start': null, 'end': null, 'collapsed': null};
    export var caret_positioned = false;

    var undo_stack = [];
    var redo_stack = [];

    /*
     * Perform a named action
     */
    export var action = function(action_name: string, new_text?: string)
    {
        try {
            selection = getSelectionPoints();
            var action_class = Actions[action_name];

            var last_item;
            var add_to_stack = true;

            if (undo_stack.length > 0 && !caret_positioned) {
                last_item = undo_stack[undo_stack.length - 1];
                if (last_item.isUpdatable() && last_item.getName() == action_name) {
                    last_item.update(new_text);
                    add_to_stack = false;
                }
            }

            if (add_to_stack) {
                undo_stack.push(new action_class(new_text));
                redo_stack = [];
            }

            caret_positioned = false;
            refreshEditorDisplay();
        }

        catch(e) {alert("Can't do!");}
    }

    /*
     * Undo last action
     */
    export var undo = function()
    {
        if (undo_stack.length > 0) {
            var last_item = undo_stack.pop();
            redo_stack.push(last_item);
            last_item.undo();
            refreshEditorDisplay();
        }
    }

    /*
     * Redo last undo
     */
    export var redo = function()
    {
        if (redo_stack.length > 0) {
            var last_item = redo_stack.pop();
            undo_stack.push(last_item);
            last_item.redo();
            refreshEditorDisplay();
        }
    }

    export function setSelection(start: number, end?: number)
    {
        end = (end == undefined) ? start : end;
        selection = {'start': start, 'end': end, 'collapsed': (start == end)};
    }

    export function selectionCollapsed()
    {
        return (selection.start == selection.end);
    }

    export function textInsert(pos, new_text)
    {
        text = text.slice(0, pos) + new_text + text.slice(pos);
    }

    export function textRemove(pos)
    {
        var removed = text.slice(pos.start, pos.end);
        text = text.slice(0, pos.start) + text.slice(pos.end);
        return removed;
    }

    export function textReplace(pos, new_text)
    {
        text = text.slice(0, pos.start) + new_text + text.slice(pos.end);
    }

    export function replaceSelection(new_text)
    {
        var old_text = selection.collapsed ? "" : text.slice(selection.start, selection.end);
        text = text.slice(0, selection.start) + new_text + text.slice(selection.end);
        return {'position': selection.start, 'text': old_text};
    }

    export function registerCaretPos()
    {
        selection = getSelectionPoints();
        caret_positioned = true;
    }

    function getSelectionPoints()
    {
        var selection = EDITOR_MAIN.contentWindow.getSelection();
        var s0 = selection.getRangeAt(0);
        var start_node = s0.startContainer;
        var start_offset = s0.startOffset;
        return {
            'start': caretPosFromNode(start_node, start_offset),
            'end': caretPosFromNode(s0.endContainer, s0.endOffset),
            'collapsed': s0.collapsed
        }
    }

    function caretPosFromNode(node, offset)
    {
        var r = document.createRange();
        r.setStart(EDITOR_MAIN_PRE, 0);
        r.setEnd(node, offset);
        var div = document.createElement('div');
        div.appendChild(r.cloneContents());
        return $(div).find('br').length + $(div).text().length;
    }
}