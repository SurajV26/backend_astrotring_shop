<div class="modal-header">
    <h5 class="modal-title">User Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

    <div class="row">

        <!-- SECTION 1: PROFILE + RATING SUMMARY -->
        <div class="col-md-3 text-center">

            <img src="{{ $user->profile_image_url ?? asset('default-user.png') }}" class="img-fluid rounded shadow mb-3"
                style="width: 85%; height: 220px; object-fit: cover;">

            <h6 class="text-primary mt-3">User Rating Summary</h6>

            <table class="table table-bordered">
                <tr>
                    <th>Total Reviews</th>
                    <td>{{ $user->reviews->count() }}</td>
                </tr>

                <tr>
                    <th>Average Rating</th>
                    <td>
                        {{ number_format($user->reviews->avg('rating') ?? 0, 1) }}

                        <span class="text-warning">
                            @for ($i = 1; $i <= 5; $i++)
                                {!! $i <= round($user->reviews->avg('rating')) ? '&#9733;' : '&#9734;' !!}
                            @endfor
                        </span>
                    </td>
                </tr>
            </table>

            <h6 class="text-primary mt-3">Latest Reviews</h6>

            <div style="max-height: 200px; overflow-y: auto;">
                @foreach ($latest_reviews as $review)
                    <div class="border rounded p-2 mb-2">
                        <strong>{{ $review->astrologer->name }}</strong><br>

                        @for ($i = 1; $i <= 5; $i++)
                            {!! $i <= $review->rating ? '&#9733;' : '&#9734;' !!}
                        @endfor
                        <br>

                        {{ $review->review ?? 'No comment' }}

                        <div class="text-muted small">
                            {{ $review->created_at->format('d M Y h:i A') }}
                        </div>
                    </div>
                @endforeach

                @if ($latest_reviews->count() === 0)
                    <p class="text-muted">No reviews yet.</p>
                @endif
            </div>

        </div>

        <!-- SECTION 2: BASIC DETAILS -->
        <div class="col-md-3">
            <h5 class="text-primary mb-2">Basic Information</h5>

            <table class="table table-bordered table-striped">
                <tr>
                    <th>User Code</th>
                    <td>{{ $user->code ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td>{{ $user->name }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $user->email }}</td>
                </tr>
                <tr>
                    <th>Country Code</th>
                    <td>{{ $user->country_code ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Mobile</th>
                    <td>{{ $user->mobile ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Gender</th>
                    <td>{{ $user->gender ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>DOB</th>
                    <td>{{ $user->dob ? $user->dob->format('d M Y') : 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Birth Time</th>
                    <td>
                        {{ $user->birth_time ? \Carbon\Carbon::parse($user->birth_time)->format('h:i A') : 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <th>Birth Place</th>
                    <td>{{ $user->birth_place ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td>{{ $user->address ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Pincode</th>
                    <td>{{ $user->pincode ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Online Status</th>
                    <td>
                        <span class="badge {{ $user->is_online ? 'bg-success' : 'bg-secondary' }}">
                            {{ $user->is_online ? 'Online' : 'Offline' }}
                        </span>
                    </td>
                </tr>
            </table>

        </div>

        <!-- SECTION 3: WALLET SUMMARY -->
        <div class="col-md-3">
            <h5 class="text-primary mb-2">Wallet Summary</h5>
            <table class="table table-bordered table-striped">
                <tr>
                    <th>Current Balance</th>
                    <td>₹ {{ number_format($user->wallet->balance ?? 0, 2) }}</td>
                </tr>

                <tr>
                    <th>Total Added</th>
                    <td>₹ {{ number_format($user->wallet->total_added ?? 0, 2) }}</td>
                </tr>

                <tr>
                    <th>Total Spent</th>
                    <td>₹ {{ number_format($user->wallet->total_spent ?? 0, 2) }}</td>
                </tr>

                <tr>
                    <th>Call Spent</th>
                    <td>₹ {{ number_format($callSummary->total_amount ?? 0, 2) }}</td>
                </tr>

                <tr>
                    <th>Chat Spent</th>
                    <td>₹ {{ number_format($chatSummary->total_amount ?? 0, 2) }}</td>
                </tr>

                <tr>
                    <th>Call Minutes</th>
                    <td>{{ floor(($callSummary->total_duration ?? 0) / 60) }} mins</td>
                </tr>

                <tr>
                    <th>Chat Minutes</th>
                    <td>{{ floor(($chatSummary->total_duration ?? 0) / 60) }} mins</td>
                </tr>
            </table>

            <h6 class="text-primary mt-3">Account Status</h6>
            <table class="table table-bordered table-striped">
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="{{ $user->status ? 'text-success' : 'text-danger' }}">
                            {{ $user->status ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Created At</th>
                    <td>{{ $user->created_at->format('d M Y, h:i A') }}</td>
                </tr>
                <tr>
                    <th>Last Seen</th>
                    <td>{{ $user->last_seen_at ? $user->last_seen_at->diffForHumans() : 'N/A' }}</td>
                </tr>
                <tr>
                    <th>About</th>
                    <td>
                        <div class="overflow-auto" style="max-height:50px; max-width:250px;">
                            {{ $user->about ?? 'N/A' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="col-md-3">
            <h5 class="text-primary mt-3">Recharge History</h5>
            <div style="max-height:300px;overflow-y:auto;">
                <table class="table table-bordered">
                    @forelse($rechargeHistory as $r)
                        <tr>
                            <td>
                                ₹ {{ number_format($r->amount, 2) }} <br>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($r->recharged_at)->format('d M Y h:i A') }}
                                </small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-muted text-center">No recharge history</td>
                        </tr>
                    @endforelse
                </table>
            </div>
        </div>

        <div class="col-md-12 mt-4">
            <h5 class="text-primary mb-2">Interaction History</h5>

            <div style="max-height: 400px; overflow-y: auto;">
                @forelse($interactionHistory as $h)
                    <div class="border rounded p-3 mb-3 shadow-sm">

                        <div class="d-flex justify-content-between">
                            <strong>
                                {{ $h->astrologer_code }} – {{ $h->astrologer_name }}
                            </strong>

                            <span class="badge {{ $h->type === 'CHAT' ? 'bg-info' : 'bg-warning' }}">
                                {{ $h->type }}
                            </span>
                        </div>

                        <table class="table table-sm table-bordered mt-2 mb-0">
                            <tr>
                                <th width="25%">Start</th>
                                <td>{{ \Carbon\Carbon::parse($h->started_at)->format('d M Y h:i A') }}</td>
                            </tr>
                            <tr>
                                <th>End</th>
                                <td>{{ \Carbon\Carbon::parse($h->ended_at)->format('d M Y h:i A') }}</td>
                            </tr>
                            <tr>
                                <th>Duration</th>
                                <td>{{ floor($h->duration / 60) }} mins</td>
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td>₹ {{ number_format($h->amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge bg-success">
                                        {{ ucfirst($h->status) }}
                                    </span>
                                </td>
                            </tr>
                        </table>

                    </div>
                @empty
                    <p class="text-muted">No interaction history found.</p>
                @endforelse
            </div>
        </div>

    </div>

</div>

<div class="modal-footer">
    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
