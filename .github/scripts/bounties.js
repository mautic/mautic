const { Octokit } = require("@octokit/core");
const octokit = new Octokit({ auth: process.env.GITHUB_TOKEN });

async function run() {
  const githubContext = JSON.parse(process.env.GITHUB_CONTEXT);
  const issueID = githubContext.event.issue.number;
  const issueTitle = githubContext.event.issue.title;
  const repoName = githubContext.event.repository.name;
  const repoOwner = githubContext.event.repository.owner.login;

  try {
    switch (githubContext.event.action) {
      case 'opened':
        const body = githubContext.event.issue.body +
          "\n\n<br /><hr>\nCare about this issue? Want to get it " +
          "resolved sooner? If you are a " +
          "<a href='https://www.mautic.org/become-a-member-of-mautic'>member " +
          "of Mautic</a>, you can add some funds to the " +
          "<a href='https://opencollective.com/mautic/projects/bounties'>Bounties Project</a> " +
          "so that the person who completes this task can claim those funds once it is " +
          "merged by a member of the core team! Read the docs " +
          "<a href='https://contribute.mautic.org/product-team/mautic-bounty-programme'>here.</a>";
        
        await octokit.request('PATCH /repos/{owner}/{repo}/issues/{issue_number}', {
          owner: repoOwner,
          repo: repoName,
          issue_number: issueID,
          body: body,
        });
        break;
    
      case 'labeled':
        const labelName = githubContext.event.label.name;

        if (labelName === 'bounty') {
          const comment =
            "This issue has a bounty associated with it. Check the total available " +
            "<a href='https://opencollective.com/mautic/projects/bounties/transactions'> " +
            "here</a>. Read the docs about how to work on the issue and claim the funds "
            "<a href='https://contribute.mautic.org/product-team/mautic-bounty-programme'>here.</a>";
      
          await octokit.request('POST /repos/{owner}/{repo}/issues/{issue_number}/comments', {
            owner: repoOwner,
            repo: repoName,
            issue_number: issueID,
            body: comment
          });
        }
        break;
  
      default:
        break;
    }
  } catch (error) {
    console.error(error);
  }
}

run();
