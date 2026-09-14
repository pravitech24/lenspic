<?php

return [
    'contact' => [
        'support_email' => env('MARKETING_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS')),
        'recipient' => env('MARKETING_CONTACT_RECIPIENT'),
        'phone' => env('MARKETING_CONTACT_PHONE'),
        'demo_video_url' => env('MARKETING_DEMO_VIDEO_URL'),
    ],
    'social' => [
        'instagram' => env('MARKETING_INSTAGRAM_URL'),
        'facebook' => env('MARKETING_FACEBOOK_URL'),
        'youtube' => env('MARKETING_YOUTUBE_URL'),
        'linkedin' => env('MARKETING_LINKEDIN_URL'),
    ],
    'legal' => [
        'effective_date' => env('LEGAL_EFFECTIVE_DATE', '2026-08-26'),
        'last_updated_date' => env('LEGAL_LAST_UPDATED_DATE', '2026-08-26'),
    ],
    'hero_sliders' => [
        'home' => [
            ['Your Group Photos, Delivered Beautifully','Create private group galleries and help every member access the moments that matter.','lenspic-slider-group-gallery','A professional photographer capturing a joyful gathering while group members enjoy a private digital gallery.','Create a Group','register','Explore Features','marketing.features','right'],
            ['Built for Modern Photographers','Upload, organize, share and manage thousands of group photographs through one streamlined platform.','lenspic-slider-photographers','A professional photographer managing group galleries from a studio laptop.','For Photographers','marketing.photographers','See How It Works','marketing.how-it-works','right'],
            ['Find Your Photos with a Selfie','Group members can optionally use consent-based facial photo matching to discover potential matches within an authorized group gallery.','lenspic-slider-find-my-photos','A group member privately reviewing potential group-photo matches on a phone.','Find My Photos','marketing.discovery','Learn About Privacy','marketing.biometric-consent','right',['position'=>'left','width'=>'wide','overlay'=>'left-strong','theme'=>'light']],
            ['Private Galleries. Meaningful Memories.','Share group photographs with the right people through secure, permission-based gallery access.','lenspic-slider-private-sharing','A private group gallery displayed across phone, tablet and laptop screens.','Create Your Group','register','Explore Security','marketing.security','right'],
            ['One Platform for Every Occasion','From weddings and birthdays to corporate gatherings and conferences, LensPic makes group photo delivery simple.','lenspic-slider-group-occasions','A cohesive collection of wedding, birthday, conference and community gathering moments.','Get Started','register','View Use Cases','marketing.solutions','right'],
        ],
    ],
    'features' => [
        'Photo delivery' => [
            ['High-quality uploads','Optimized media variants and controlled private delivery.','Available'],
            ['Responsive galleries','Group galleries designed for phones, tablets and desktops.','Available'],
            ['Batch processing','Queued uploads with visible processing status and retry controls.','Available'],
            ['Secure downloads','Owner-controlled downloads through authorized media delivery.','Available'],
        ],
        'AI and personalization' => [
            ['Face recognition','Help group members locate relevant group photos.','Included in selected plans'],
            ['Consent-based selfie matching','Explicit consent is required before biometric matching.','Included in selected plans'],
            ['Personalized My Photos','Matched results stay associated with the authorized participant.','Included in selected plans'],
            ['Biometric controls','Withdrawal, retention and deletion workflows are built into LensPic.','Available'],
        ],
        'Gallery management' => [
            ['Groups and folders','Organize groups, folders, cover images and members.','Available'],
            ['Invite links and QR access','Share revocable invitations and six-character access codes.','Available'],
            ['Favourites','Let clients build a clear selection list.','Available'],
            ['Download permissions','Control individual and bulk download access per gallery.','Available'],
            ['Slideshow','Present gallery photos in a focused viewing experience.','Available'],
        ],
        'Business tools' => [
            ['Business branding','Apply approved studio details and a private-media logo.','Included in selected plans'],
            ['Watermarks','Protect delivered previews with configurable watermark settings.','Included in selected plans'],
            ['Portfolio','Publish selected work through a dedicated portfolio.','Included in selected plans'],
            ['Team access','Invite studio members with roles and seat limits.','Included in selected plans'],
            ['Analytics and notifications','Review activity and receive relevant workspace updates.','Included in selected plans'],
            ['Wallet and transactions','Manage supported notification credits and financial records.','Included in selected plans'],
            ['Digital flipbook','Create a gallery-linked flipbook when enabled for the account.','Included in selected plans'],
        ],
    ],
    'solutions' => [
        'photographers' => ['nav'=>'For Photographers','title'=>'Photo delivery built for working photographers','route'=>'solutions.photographers','seo_description'=>'Create LensPic Groups, process large photo collections, control private galleries, team access, branding, storage and downloads.','intro'=>'Run repeatable client-delivery workflows without turning every gallery into a manual sorting project.','challenges'=>['Large uploads and processing queues','Keeping client galleries private and organized','Coordinating team access, storage and subscription limits'],'solutions'=>['Create Groups with details, covers and organized folders','Track background processing and storage usage','Invite team members and control gallery downloads'],'features'=>['Private Group Galleries','Queued photo processing','Team roles and seat limits','Business branding','Optional consent-based discovery','Plan-aware storage controls'],'workflow'=>['Create and configure a Group','Upload and organize photographs','Review processing and permissions','Share supported links or access codes','Deliver favourites and permitted downloads'],'privacy'=>'Original media remains behind authorized delivery routes. Optional selfie matching starts only after explicit consent and operates within the authorized Group.','faqs'=>[['Can I control downloads?','Yes. Group owners can enable or disable supported download options without deleting stored photographs.'],['Can my studio collaborate?','Supported plans allow owners to invite team members within the configured seat limit.']],'primary'=>['Create a Group','register'],'secondary'=>['Explore Features','marketing.features']],
        'weddings' => ['nav'=>'For Weddings','title'=>'Private wedding galleries for every celebration','route'=>'solutions.weddings','seo_description'=>'Deliver engagement, Mehendi, Sangeet, ceremony and reception photographs through private LensPic Groups.','intro'=>'Give photographers, couples and families a controlled way to receive photographs across large wedding celebrations.','challenges'=>['Thousands of photographs across several functions','Sharing privately with extended families and guests','Helping each person reach relevant photographs'],'solutions'=>['Create a separate Group for each function when appropriate','Invite authorized family members and guests','Offer favourites, permitted downloads and optional discovery'],'features'=>['Private invitations','Function-based Groups','Favourites and selections','Download permissions','Responsive galleries','Consent-based photo discovery'],'workflow'=>['Create the wedding Group','Add a cover and function details','Upload the photographer collection','Invite the intended members','Deliver favourites and permitted downloads'],'privacy'=>'Wedding photographs are not made public by this page. Owners choose access and download settings; biometric matching remains optional and consent-based.','faqs'=>[['Can wedding functions be separated?','Yes. A photographer may create separate Groups for an engagement, Mehendi, Sangeet, ceremony or reception when that suits the delivery workflow.'],['Do guests need the selfie feature?','No. Authorized members can browse permitted galleries without using optional photo discovery.']],'primary'=>['Create a Wedding Group','register'],'secondary'=>['See How It Works','marketing.how-it-works']],
        'celebrations' => ['nav'=>'For Parties & Celebrations','title'=>'Keep celebration photographs close to the people invited','route'=>'solutions.celebrations','seo_description'=>'Share birthdays, anniversaries, reunions and cultural celebration photographs through controlled LensPic Groups.','intro'=>'Replace broad public folders with an invitation-based experience for families, friends and community members.','challenges'=>['Mixed audiences and personal family photographs','Unclear download permissions','Large collections that are difficult to browse'],'solutions'=>['Create a private Group for the celebration','Share controlled access with invited members','Enable favourites, downloads and optional discovery as appropriate'],'features'=>['Invitation-based access','Responsive galleries','Favourites','Owner-controlled downloads','Organized folders','Optional photo discovery'],'workflow'=>['Create a celebration Group','Upload and organize photos','Set access and download choices','Invite members','Browse, favourite and download when permitted'],'privacy'=>'The Group owner controls access. LensPic does not identify members automatically; optional matching requires the member’s explicit consent.','faqs'=>[['Which celebrations fit this workflow?','Birthdays, anniversaries, reunions, cultural celebrations and similar private gatherings can each use a LensPic Group.'],['Can downloads be restricted?','Yes. Owners can configure download permissions for the gallery.']],'primary'=>['Create a Celebration Group','register'],'secondary'=>['Explore Features','marketing.features']],
        'corporate' => ['nav'=>'For Corporate Groups','title'=>'Professional photo delivery for corporate groups','route'=>'solutions.corporate','seo_description'=>'Organize company gatherings, conferences, launches and team photographs with restricted LensPic Group access.','intro'=>'Give authorized staff and attendees a consistent gallery while keeping professional delivery and permissions under control.','challenges'=>['High-volume photography from multiple activities','Internal or restricted audiences','Brand and team coordination'],'solutions'=>['Use restricted Group invitations and organized folders','Coordinate team roles and background uploads','Apply supported branding and download controls'],'features'=>['Restricted Group access','Team roles','Large queued uploads','Business branding','Download controls','Consent-based matching'],'workflow'=>['Create the corporate Group','Assign team access where available','Upload and organize the collection','Configure invitations and permissions','Share the controlled gallery'],'privacy'=>'LensPic makes no unverified compliance-certification claims. Owners remain responsible for appropriate notices and access decisions; matching is optional and consent-based.','faqs'=>[['Can a gallery be restricted?','Yes. Supported invitations and Group access controls can limit the delivery audience.'],['Is face matching automatic?','No. It is available only in enabled journeys after explicit consent.']],'primary'=>['Create a Corporate Group','register'],'secondary'=>['Book a Demo','marketing.contact']],
        'institutions' => ['nav'=>'For Colleges & Institutions','title'=>'Organized galleries for colleges and institutions','route'=>'solutions.institutions','seo_description'=>'Deliver graduation, annual function, sports, workshop and academic conference photographs through controlled LensPic Groups.','intro'=>'Help authorized organizers and photographers separate large institutional collections while protecting member choice.','challenges'=>['Several programmes with different audiences','Large photography collections','Special care around students and minors'],'solutions'=>['Create separate Groups for appropriate programmes','Share access only with intended members','Allow browsing, permitted downloads and voluntary discovery'],'features'=>['Separate Groups and folders','Controlled invitations','Large collection processing','Download permissions','Responsive member access','Explicit-consent matching'],'workflow'=>['Confirm the authorized organizer','Create programme Groups','Upload and organize photographs','Set access and download permissions','Invite members through supported methods'],'privacy'=>'LensPic does not identify students without consent and should not be used for surveillance. Organizations must apply suitable privacy handling, especially where minors are involved.','faqs'=>[['Can annual functions and sports activities be separated?','Yes. Authorized organizers can create distinct Groups for clearer access and delivery.'],['Does LensPic identify students automatically?','No. Optional matching requires explicit consent and an authorized Group context.']],'primary'=>['Create an Institution Group','register'],'secondary'=>['Read About Privacy','marketing.privacy']],
        'conferences' => ['nav'=>'For Conferences & Community Gatherings','title'=>'Group photo delivery for conferences and communities','route'=>'solutions.conferences','seo_description'=>'Organize conference, seminar, exhibition, workshop and community programme photographs in controlled LensPic galleries.','intro'=>'Connect photographer and organizer workflows with a straightforward member experience for professional and community gatherings.','challenges'=>['Many attendees and sessions','Fast but controlled distribution','Keeping highlights and full galleries organized'],'solutions'=>['Organize photographs by Group and folder','Use authorized links, codes and permission-controlled galleries','Support favourites, downloads and optional discovery'],'features'=>['Group-based organization','QR-ready invitation links','Private galleries','Favourites','Photographer team workflow','Optional consent-based discovery'],'workflow'=>['Create the gathering Group','Organize sessions or highlights','Upload and process photographs','Share authorized access','Let members browse and download when permitted'],'privacy'=>'Access rules and download choices remain with the Group owner. Selfie-based discovery is optional, silent by default and requires explicit consent.','faqs'=>[['Can attendees join without an app?','Yes. LensPic provides a responsive web experience using supported Group links or access methods.'],['Can organizers highlight selected photographs?','Groups can use folders and covers to organize a clearer delivery experience.']],'primary'=>['Create a Group','register'],'secondary'=>['See How It Works','marketing.how-it-works']],
    ],
    'faqs' => [
        'Getting Started' => [
            ['How do I start using LensPic?','Create an account, complete your profile and create a group from the workspace.'],
            ['Do group members need a mobile app?','No. LensPic provides a responsive web experience through supported browsers.'],
        ],
        'Groups and Invitations' => [
            ['How can someone join a group?','They can use a valid invitation link, QR code or six-character access code supplied by the group owner.'],
            ['Can an invitation be revoked?','Yes. Owners can revoke or regenerate supported invitation links and access codes.'],
        ],
        'Uploading and Image Quality' => [
            ['How are uploaded photographs delivered?','LensPic stores private originals and creates optimized variants for responsive delivery according to the configured processing profile.'],
        ],
        'Face Recognition and Privacy' => [
            ['Is a selfie required to browse every gallery?','No. Selfies are used only for enabled face-matching journeys. Explicit biometric consent is required before matching.'],
            ['Can biometric consent be withdrawn?','LensPic includes consent withdrawal and biometric deletion-request workflows. Availability depends on the gallery and account configuration.'],
        ],
        'Gallery and Downloads' => [
            ['Can photographers disable downloads?','Yes. Gallery owners can configure download permissions without deleting the underlying media.'],
        ],
        'Photographer Tools' => [
            ['Can I add my studio branding?','Business branding, portfolio and watermark controls are available according to plan and account settings.'],
            ['Can team members access my studio?','Supported plans allow owners to invite team members within the configured seat limit.'],
        ],
        'Plans and Billing' => [
            ['Where do plan limits come from?','The pricing page reads active quota records from the same entitlement source used by media and team enforcement.'],
            ['Are taxes included?','Checkout displays the configured INR amount including applicable GST. Review the final order before payment.'],
        ],
        'Wallet and Notifications' => [
            ['What are wallet credits for?','Wallet credits support configured paid notification services. Charges and availability are shown inside the authenticated workspace.'],
        ],
        'Portfolio, Flipbook and Watermark' => [
            ['Are portfolio and flipbook tools always included?','They are available only where enabled and permitted by the selected plan.'],
        ],
        'Troubleshooting' => [
            ['My group code does not work. What should I do?','Check the six characters and ask the organizer for a current invitation. Invalid, expired and revoked codes fail safely.'],
        ],
    ],
    'policies' => [
        'privacy' => [
            'title' => 'Privacy Policy', 'description' => 'How LensPic processes account, group, media, member and face-matching information.',
            'sections' => [
                ['Information we collect',['Account and studio details supplied during registration or profile setup.','Group, gallery, folder, invitation and access information.','Uploaded photographs, private media, derived variants and associated metadata.','Group membership, favourites, download activity and support enquiries.','A consented selfie, derived facial reference and potential match results when Find My Photos is used.','Device, session, security and diagnostic information needed to operate and protect the service.']],
                ['Why we process information','We use information to provide accounts, organize and deliver group galleries, enforce access and plan limits, process payments, send configured notifications, answer enquiries, prevent misuse and maintain service reliability. Face information is processed only for an enabled, authorized group after the member provides explicit consent.'],
                ['Cookies','LensPic uses cookies and similar browser storage needed for sessions, security, preferences and core application behavior. Optional analytics are loaded only when separately configured and permitted.'],
                ['Service providers and sharing','Limited information may be processed by configured infrastructure, private storage, email, payment, messaging and face-recognition providers to deliver their contracted function. LensPic may also disclose information where required to address valid legal or security obligations.'],
                ['Storage and security','LensPic uses private media storage, authorized delivery, access controls, signed delivery where configured, and operational safeguards. No internet service can promise absolute security.'],
                ['Retention','Retention depends on the record type, account state, configured media lifecycle, consent status and obligations that require particular records to be preserved. See the Data Retention Policy for the product workflows currently implemented.'],
                ['Your choices and rights','Depending on applicable requirements, users may request access, correction or deletion, withdraw biometric consent, reject potential matches and use supported account or gallery controls. Identity or account authorization may be verified before acting on a request.'],
                ['Children’s privacy','Group owners using LensPic in connection with minors are responsible for establishing appropriate authorization, consent and restricted access. LensPic does not make a blanket claim that every group workflow is suitable for children.'],
                ['Policy updates','This policy may be updated as LensPic functionality or applicable requirements change. The latest update date appears at the top of this page.'],
                ['Contact','Use the LensPic contact form for privacy questions or the Data Deletion page for deletion guidance.'],
            ],
        ],
        'terms' => [
            'title' => 'Terms and Conditions', 'description' => 'Terms for LensPic accounts, group galleries, subscriptions and acceptable platform use.',
            'sections' => [
                ['Acceptance and eligibility','By creating an account or using LensPic, you agree to these terms. You must be able to enter an agreement and provide accurate account information.'],
                ['Account responsibilities','Keep credentials secure, maintain accurate details and promptly report suspected unauthorized access. Studio owners are responsible for team invitations and assigned access.'],
                ['Photographer and group-owner responsibilities',['Upload only media you are permitted to process and deliver.','Configure group invitations, privacy and downloads appropriately.','Provide members with required notices and obtain valid consent before enabling face matching.','Do not expose group codes or private galleries beyond the intended audience.']],
                ['Group-member responsibilities','Use only invitations you are authorized to use, respect group-owner download controls and do not submit another person’s selfie or attempt to discover photographs outside the authorized group.'],
                ['Media ownership and processing permission','You retain ownership of content you upload. You grant LensPic the limited permission needed to store, optimize, analyze and deliver that content according to your settings and requested features.'],
                ['Face-recognition feature','Find My Photos compares a consented facial reference with photographs inside an authorized group to return potential matches. It does not identify unknown people. Results may contain false matches or miss relevant photographs and should be reviewed by the user.'],
                ['Subscriptions, payments and refunds','Plan prices, billing periods, taxes and active entitlements are shown through the pricing and checkout workflows. Captured payments activate the selected term. Refund and cancellation handling follows the Refund and Cancellation Policy.'],
                ['Prohibited use','Do not upload unlawful or infringing content, misuse biometric features, evade access controls, distribute malware, interfere with service operation or use LensPic to harm others.'],
                ['Availability, suspension and termination','LensPic may be interrupted for maintenance, provider incidents or security response. Access may be restricted for material misuse, legal necessity, payment failure or risks to the service or other users.'],
                ['Liability and responsibility','LensPic is provided subject to the limitations permitted by applicable requirements. Users remain responsible for their content, permissions, group configuration and decisions based on potential face matches.'],
                ['Changes and contact','Features and these terms may change. Material updates will be reflected by the last-updated date. Questions can be submitted through the contact form.'],
            ],
        ],
        'refunds' => [
            'title' => 'Refund and Cancellation Policy', 'description' => 'How LensPic handles subscription cancellation, payment issues and wallet credits.',
            'sections' => [
                ['Subscriptions and renewal','LensPic currently activates a quarterly or yearly paid term after Razorpay verifies a captured payment. The current implementation does not silently publish a separate automatic-renewal promise. Review the checkout details before payment.'],
                ['Cancellation','Contact LensPic through the billing enquiry workflow if you need help with an active subscription. Cancellation does not by itself reverse a completed payment or consumed service.'],
                ['Upgrades and plan changes','Available upgrades are shown in the authenticated subscription settings. The system extends the active plan term from the later of the current expiry or purchase time. Unsupported downgrade or proration behavior is not promised.'],
                ['Duplicate, failed or reversed payments','LensPic verifies provider order, amount, currency and captured status before activation. Duplicate webhook processing is handled idempotently. Report a suspected duplicate charge or reversal with the provider transaction reference; never send card or banking credentials.'],
                ['Refund assessment','A refund is not guaranteed merely because a request is submitted. LensPic reviews verified duplicate charges, incorrect processing and other circumstances required by applicable requirements. Any approved return uses the supported provider process.'],
                ['Wallet credits','Wallet top-ups support configured paid notification services. Credits are not represented as cash and consumed credits are not automatically refundable. Payment reversals and duplicate top-ups are reviewed against ledger and provider records.'],
                ['Taxes and contact','Tax treatment follows the verified transaction. Submit billing requests through the contact form with the account email and transaction reference.'],
            ],
        ],
        'cookies' => [
            'title' => 'Cookie Policy', 'description' => 'Browser storage used by the LensPic website and authenticated application.',
            'sections' => [
                ['Essential cookies','LensPic uses cookies required for secure sessions, CSRF protection, authentication and application preferences. Disabling them may prevent account and form workflows from functioning.'],
                ['Optional measurement','LensPic does not load a random analytics provider. If optional analytics are configured, they must follow the published privacy choices and must not receive private gallery, selfie or biometric identifiers.'],
                ['Managing cookies','Browser controls can remove or block cookies. Essential account functions may require you to sign in again or may stop working when session storage is blocked.'],
            ],
        ],
        'security' => [
            'title' => 'Data Protection and Security', 'description' => 'The access, storage and operational safeguards used by LensPic.',
            'sections' => [
                ['Private media architecture','Original media is stored on configured private disks. Delivery occurs through authorization-aware application routes or time-limited signed delivery when configured. Private storage paths are not intended for public exposure.'],
                ['Access and application safeguards',['Authenticated workspace routes remain protected.','Policies scope gallery and media actions to authorized users.','CSRF, validation and rate limits protect supported public forms.','Sensitive credentials remain in server-side configuration.']],
                ['Processing safeguards','Uploads use validation, queued processing and controlled variants. Biometric consent and deletion operations are recorded through dedicated workflows.'],
                ['Shared responsibility','Account owners must protect credentials, invitations and devices and configure galleries appropriately. No security program can eliminate every risk. Report suspected security issues through the contact workflow without including passwords or sensitive media.'],
            ],
        ],
        'biometric-consent' => [
            'title' => 'Biometric Data and Face-Recognition Consent', 'description' => 'How optional Find My Photos matching works and how participants remain in control.',
            'sections' => [
                ['What the feature does','LensPic compares a selfie or facial reference provided by a consenting member with photographs inside one authorized group gallery. It returns potentially relevant photographs; it does not identify unknown individuals.'],
                ['Information processed','The workflow may process the submitted selfie, a derived facial representation, group-specific indexed faces, potential match scores, review outcomes and consent records.'],
                ['Consent and optional participation','Face matching starts only after the user receives the relevant notice and explicitly consents. Participants can use permitted gallery browsing without claiming that face matching is mandatory for every gallery.'],
                ['Accuracy limitations','Similarity matching can return false matches or miss photographs. Lighting, pose, image quality and appearance changes can affect results. Users should review potential matches and can reject or request review of supported results.'],
                ['Withdrawal, retention and deletion','Supported workflows allow consent withdrawal and tracked biometric deletion requests. Withdrawal stops future processing under that consent but may not remove records that must temporarily remain for security, audit or valid operational obligations.'],
                ['Learn more','Read the Privacy Policy and Data Deletion page. Contact support if you cannot reach the authenticated deletion workflow.'],
            ],
        ],
        'retention' => [
            'title' => 'Data Retention Policy', 'description' => 'How LensPic lifecycle settings affect account, media and biometric records.',
            'sections' => [
                ['Retention principles','LensPic keeps information only while needed for the product workflow, account relationship, security, transaction integrity or applicable obligations. A single universal retention period is not promised.'],
                ['Deleted gallery media','Deleted media may remain restorable during the plan-configured release window before queued permanent removal. The authoritative duration comes from the active plan entitlement.'],
                ['Temporary selfies and face results','Selfie and face-result retention follow configured biometric lifecycle settings and deletion operations. Consent withdrawal and deletion requests are tracked separately from ordinary gallery removal.'],
                ['Financial and security records','Payment, wallet, audit and security records may remain where needed to reconcile transactions, investigate misuse or meet applicable obligations.'],
                ['Account closure','Closing an account does not necessarily remove every record immediately. LensPic separates content deletion, provider deletion and records that must be retained for legitimate operational or legal reasons.'],
            ],
        ],
        'data-deletion' => [
            'title' => 'Data Deletion Request', 'description' => 'Use the correct authenticated or supported workflow to request deletion of LensPic data.',
            'sections' => [
                ['Gallery and photograph deletion','Authorized group owners can delete photographs and groups through existing workspace controls. Media may remain in a plan-configured restorable state before the permanent purge job runs.'],
                ['Selfie and matching-data deletion','An authenticated group member can withdraw biometric consent and submit the supported biometric deletion request from the group discovery workflow. LensPic tracks local deletion and configured provider deletion operations.'],
                ['Account or other personal information','For account-level or other requests, submit a Privacy or Technical Support enquiry using the account email. LensPic may require authentication or additional verification before acting.'],
                ['Processing steps',['The request is associated with the authorized account or consent record.','Relevant local and provider operations are queued or tracked.','The requester can use supported status workflows or receive a response through the verified contact channel.','Records subject to valid security, transaction or legal retention are separated from deletable content.']],
                ['Start a request','Sign in to use group or account controls. If you cannot access the relevant account, use the LensPic contact form and select Technical Support. A request is not represented as complete until the underlying workflow confirms it.'],
            ],
        ],
        'acceptable-use' => [
            'title' => 'Acceptable Use Policy', 'description' => 'Rules that help protect LensPic users, galleries and infrastructure.',
            'sections' => [
                ['Permitted use','Use LensPic for lawful group-photo organization and delivery with appropriate rights, invitations and member notices.'],
                ['Prohibited content and conduct',['Content that is unlawful, infringing, abusive or uploaded without necessary rights.','Non-consensual biometric processing or submitting another person’s selfie.','Attempts to enumerate groups, bypass authorization or expose private media.','Malware, automated abuse, credential attacks or disruption of service operation.','Misrepresentation, harassment or use that creates a material safety risk.']],
                ['Enforcement','LensPic may restrict content or accounts, preserve evidence needed for investigation and cooperate with valid legal processes where appropriate.'],
            ],
        ],
        'copyright' => [
            'title' => 'Copyright and Intellectual Property Policy', 'description' => 'Ownership, platform rights and reporting concerns about uploaded media.',
            'sections' => [
                ['Your photographs','Photographers and other uploaders retain their ownership rights. Uploading grants LensPic only the permission needed to store, process, optimize and deliver media through the selected product features.'],
                ['LensPic materials','LensPic software, interface, branding and original public content remain protected by applicable intellectual-property rights. These terms do not transfer ownership of the platform.'],
                ['Respecting third-party rights','Do not upload or distribute media, logos, music or other material unless you have the rights and permissions required for that use.'],
                ['Reporting a concern','Use the contact form and provide the affected URL or group reference, a clear description of the work, your relationship to it and a reliable contact method. Do not include account passwords or private storage credentials.'],
            ],
        ],
    ],
    'posts' => [
        'private-group-photo-delivery' => [
            'title' => 'A practical guide to private group photo delivery',
            'excerpt' => 'Plan gallery access, invitations and download permissions before the first photograph is uploaded.',
            'category' => 'Delivery', 'author' => 'LensPic Team', 'published_at' => '2026-08-20',
            'seo_description' => 'A practical LensPic guide to private group photo delivery, gallery access and download controls.',
            'content' => [
                ['Start with the audience','Decide who should access the gallery, whether registration is required and when an invitation should expire. This avoids sharing a broad public folder by default.'],
                ['Set delivery controls','Choose gallery privacy and download permissions before inviting participants. Keep originals private and deliver only authorized variants.'],
                ['Explain personalized matching','If face matching is enabled, tell group members why a selfie is requested and obtain explicit consent before biometric processing.'],
            ],
        ],
        'photographer-gallery-workflow' => [
            'title' => 'Building a repeatable client gallery workflow',
            'excerpt' => 'A consistent group, folder, branding and favourites workflow reduces manual hand-offs.',
            'category' => 'For photographers', 'author' => 'LensPic Team', 'published_at' => '2026-08-12',
            'seo_description' => 'Organize a repeatable photographer workflow with LensPic groups, folders, branding and favourites.',
            'content' => [
                ['Create a consistent structure','Use predictable group and folder names so studio teammates can understand a group without relying on private notes.'],
                ['Prepare the client experience','Add approved branding, a cover image and download settings before sharing the invitation.'],
                ['Close the feedback loop','Ask clients to use favourites for selections so the final list remains attached to the gallery workflow.'],
            ],
        ],
    ],
];
