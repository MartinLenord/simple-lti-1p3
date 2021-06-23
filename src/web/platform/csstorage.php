<script>
!function(w) {
    let stored_data = {};
    w.addEventListener("message", function(event) {
        console.log(w.location.origin + " Got post message from " + event.origin);
        console.log(JSON.stringify(event.data, null, '    '));
        switch(event.data.subject) {
            case 'org.imsglobal.lti.put_data':
                if (!stored_data[event.origin]) {
                    stored_data[event.origin] = {};
                }
                stored_data[event.origin][event.data.key] = event.data.value;
                let send_data = {
                    subject: 'org.imsglobal.lti.put_data.response',
                    message_id: event.data.message_id,
                    key: event.data.key,
                    value: event.data.value,
                };
                console.log(w.location.origin + " Sending post message to " + event.origin);
                console.log(JSON.stringify(send_data, null, '    '));
                event.source.postMessage(send_data, event.origin);
                return;
            case 'org.imsglobal.lti.get_data':
                if (stored_data[event.origin] && stored_data[event.origin][event.data.key]) {
                    let send_data = {
                        subject: 'org.imsglobal.lti.get_data.response',
                        message_id: event.data.message_id,
                        key: event.data.key,
                        value: stored_data[event.origin][event.data.key],
                    };
                    console.log(w.location.origin + " Sending post message to " + event.origin);
                    console.log(JSON.stringify(send_data, null, '    '));
                    event.source.postMessage(send_data, event.origin);
                } else {
                    let send_data = {
                        subject: 'org.imsglobal.lti.get_data.response',
                        message_id: event.data.message_id,
                        key: event.data.key,
                        error: 'Could not find key',
                    };
                    console.log(w.location.origin + " Sending post message to " + event.origin);
                    console.log(JSON.stringify(send_data, null, '    '));
                    event.source.postMessage(send_data, event.origin);

                }
                return;
            default:
                console.log('unknown subject');
        }
    }, false);
}(window);
</script>